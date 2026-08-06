<?php

use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Models\SurveyAnswer;
use App\Models\SurveySubmission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('stores one submission and exactly 26 answers atomically', function () {
    $fixture = surveyFixture();
    $data = new SubmitSurveyData(
        periodId: $fixture->period->id,
        respondentId: $fixture->respondent->id,
        sessionId: $fixture->session->id,
        unitId: $fixture->unit->id,
        idempotencyKey: '11111111-1111-4111-8111-111111111111',
        instrumentVersion: $fixture->period->instrument_version,
        startedAt: now()->subMinutes(4)->toImmutable(),
        answers: array_fill_keys(range(1, 26), 4),
    );

    $submission = app(SubmitSurvey::class)->handle($data);

    expect(SurveySubmission::count())->toBe(1)
        ->and(SurveyAnswer::where('survey_submission_id', $submission->id)->count())->toBe(26);
});

it('persists prospective quality flags before a manual review decision', function () {
    $fixture = surveyFixture();

    $submission = app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));
    $review = $submission->fresh('qualityReview')->qualityReview;

    expect($review)->not->toBeNull()
        ->and($review->flags)->toBe([
            'fast_completion' => false,
            'identical_answers' => true,
        ])
        ->and($review->decision)->toBeNull()
        ->and($review->reviewed_by)->toBeNull()
        ->and($review->reviewed_at)->toBeNull();
});

it('returns the original submission for the same idempotency key', function () {
    $fixture = surveyFixture();
    $data = validSubmitSurveyData($fixture);
    $action = app(SubmitSurvey::class);

    expect($action->handle($data)->id)->toBe($action->handle($data)->id)
        ->and(SurveySubmission::count())->toBe(1);
});

it('rejects a second submission for the same respondent period and unit', function () {
    $fixture = surveyFixture();
    app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));
    $second = validSubmitSurveyData($fixture, idempotencyKey: '22222222-2222-4222-8222-222222222222');

    expect(fn () => app(SubmitSurvey::class)->handle($second))
        ->toThrow(DomainException::class, 'Modul ini sudah pernah dinilai.');
});

it('rejects a non-integer score before opening a submission transaction', function () {
    $fixture = surveyFixture();
    $answers = array_fill_keys(range(1, 26), 4);
    $answers[1] = '4';

    expect(fn () => new SubmitSurveyData(
        periodId: $fixture->period->id,
        respondentId: $fixture->respondent->id,
        sessionId: $fixture->session->id,
        unitId: $fixture->unit->id,
        idempotencyKey: '33333333-3333-4333-8333-333333333333',
        instrumentVersion: $fixture->period->instrument_version,
        startedAt: now()->subMinutes(4)->toImmutable(),
        answers: $answers,
    ))->toThrow(InvalidArgumentException::class, 'Nilai jawaban harus berupa bilangan bulat.');
});

it('stores duration in whole seconds', function () {
    $fixture = surveyFixture();

    $submission = app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));
    $duration = DB::table('survey_submissions')->where('id', $submission->id)->value('duration_seconds');

    expect($duration)->toBeInt();
});

it('rolls back the submission session and answers when answer insertion fails', function () {
    $fixture = surveyFixture();
    $event = 'eloquent.creating: '.SurveyAnswer::class;
    Event::listen($event, static fn () => throw new RuntimeException('Simulated answer insertion failure.'));

    try {
        expect(fn () => app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture)))
            ->toThrow(RuntimeException::class, 'Simulated answer insertion failure.');
    } finally {
        Event::forget($event);
    }

    expect(SurveySubmission::count())->toBe(0)
        ->and(SurveyAnswer::count())->toBe(0)
        ->and($fixture->session->fresh()->submitted_count)->toBe(0);
});

it('rejects a direct submission after the survey window closes', function () {
    $fixture = surveyFixture();
    $fixture->period->update(['closes_at' => now()->subMinute()]);

    expect(fn () => app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture)))
        ->toThrow(DomainException::class, 'Periode penelitian sudah ditutup.');

    expect(SurveySubmission::count())->toBe(0)
        ->and(SurveyAnswer::count())->toBe(0)
        ->and($fixture->session->fresh()->submitted_count)->toBe(0);
});

it('rejects a direct submission from an ineligible respondent', function () {
    $fixture = surveyFixture();
    $fixture->respondent->profiles()->update(['eligible' => false]);

    expect(fn () => app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture)))
        ->toThrow(DomainException::class, 'Responden tidak memenuhi syarat.');

    expect(SurveySubmission::count())->toBe(0)
        ->and(SurveyAnswer::count())->toBe(0);
});

it('rejects a direct submission with a mismatched instrument version', function () {
    $fixture = surveyFixture();
    $data = validSubmitSurveyData($fixture);
    $data = new SubmitSurveyData(
        periodId: $data->periodId,
        respondentId: $data->respondentId,
        sessionId: $data->sessionId,
        unitId: $data->unitId,
        idempotencyKey: $data->idempotencyKey,
        instrumentVersion: 'UEQ-TAMPERED',
        startedAt: $data->startedAt,
        answers: $data->answers,
    );

    expect(fn () => app(SubmitSurvey::class)->handle($data))
        ->toThrow(DomainException::class, 'Versi instrumen tidak sesuai.');

    expect(SurveySubmission::count())->toBe(0)
        ->and(SurveyAnswer::count())->toBe(0);
});
