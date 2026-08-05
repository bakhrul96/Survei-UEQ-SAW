<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Quality\ReviewSubmission;
use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Quality\QualityDecision;
use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use App\Models\UeqBenchmark;
use App\Models\UeqResult;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use DomainException;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);

    $this->period = EvaluationPeriod::query()->firstOrFail();
    $this->period->update([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addDay(),
        'instrument_source' => 'Instrumen UEQ tervalidasi',
        'instrument_verified_at' => now(),
    ]);
    UeqBenchmark::query()
        ->where('version', $this->period->instrument_version)
        ->update(['verified_at' => now()]);

    $this->admin = User::factory()->create();
    $this->unit = EvaluationUnit::query()->firstOrFail();
    $this->submission = calculationSubmission($this->period, $this->unit, array_fill_keys(range(1, 26), 4));
    $this->secondSubmission = calculationSubmission($this->period, $this->unit, array_combine(
        range(1, 26),
        array_map(fn (int $order): int => $order % 2 === 0 ? 3 : 5, range(1, 26)),
    ));
    $this->excludedSubmission = calculationSubmission($this->period, $this->unit, array_fill_keys(range(1, 26), 1));

    $reviews = app(ReviewSubmission::class);
    $reviews->handle($this->submission, $this->admin, QualityDecision::Included, null);
    $reviews->handle($this->secondSubmission, $this->admin, QualityDecision::Included, null);
    $reviews->handle($this->excludedSubmission, $this->admin, QualityDecision::Excluded, 'Pola tidak layak.');
});

it('writes a preview with non-negative gaps and reproducible input hash', function () {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);
    $repeat = app(CalculationRunService::class)->preview($this->period, $this->admin);

    expect($run->status)->toBe('preview')
        ->and($run->input_hash)->toHaveLength(64)
        ->and($repeat->input_hash)->toBe($run->input_hash)
        ->and($run->ueqResults->every(fn (UeqResult $row) => (float) $row->gap >= 0))->toBeTrue()
        ->and($run->input_snapshot['items'][0]['positive_pole'])->toBe('right')
        ->and($run->input_snapshot['included_submission_ids'])->toBe([$this->submission->id, $this->secondSubmission->id])
        ->and($run->input_snapshot['excluded_submission_ids'])->toBe([$this->excludedSubmission->id])
        ->and($run->input_snapshot['included_raw_answers'][(string) $this->submission->id]['1'])->toBe(4);
});

it('marks older preview stale after quality change', function () {
    $run = app(CalculationRunService::class)->preview($this->period, $this->admin);
    app(ReviewSubmission::class)->handle($this->submission, $this->admin, QualityDecision::Excluded, 'Pola tidak layak.');

    expect($run->fresh()->status)->toBe('stale');
});

it('rejects a benchmark without a source or verification timestamp by scale', function () {
    UeqBenchmark::query()
        ->where('version', $this->period->instrument_version)
        ->where('scale', 'Novelty')
        ->update(['source' => '', 'verified_at' => null]);

    expect(fn () => app(CalculationRunService::class)->preview($this->period, $this->admin))
        ->toThrow(DomainException::class, 'Benchmark Novelty harus memiliki sumber dan waktu verifikasi.');
});

function calculationSubmission(EvaluationPeriod $period, EvaluationUnit $unit, array $answers): SurveySubmission
{
    $issued = app(SurveyTokenService::class)->issue();
    RespondentProfile::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
        'eligible' => true,
    ]);
    $session = SurveySession::factory()->create([
        'evaluation_period_id' => $period->id,
        'anonymous_respondent_id' => $issued->respondent->id,
    ]);

    return app(SubmitSurvey::class)->handle(new SubmitSurveyData(
        periodId: $period->id,
        respondentId: $issued->respondent->id,
        sessionId: $session->id,
        unitId: $unit->id,
        idempotencyKey: (string) Str::uuid(),
        instrumentVersion: $period->instrument_version,
        startedAt: now()->subMinutes(4)->toImmutable(),
        answers: $answers,
    ));
}
