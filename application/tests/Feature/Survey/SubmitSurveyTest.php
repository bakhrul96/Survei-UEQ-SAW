<?php

use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Models\SurveyAnswer;
use App\Models\SurveySubmission;
use Illuminate\Support\Facades\DB;

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
