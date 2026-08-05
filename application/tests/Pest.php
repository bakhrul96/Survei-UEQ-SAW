<?php

use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use App\Models\UeqItem;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function surveyFixture(): object
{
    $version = 'UEQ-TEST-'.Str::uuid();
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'instrument_version' => $version,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
        'configuration_locked_at' => now(),
    ]);
    $unit = EvaluationUnit::factory()->create(['code' => 'unit-'.Str::lower(Str::random(8))]);
    foreach (range(1, 26) as $order) {
        UeqItem::factory()->create(['version' => $version, 'order' => $order]);
    }
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

    return (object) [
        'period' => $period,
        'unit' => $unit,
        'respondent' => $issued->respondent,
        'plainToken' => $issued->plainToken,
        'session' => $session,
    ];
}

function validSubmitSurveyData(object $fixture, ?string $idempotencyKey = null): SubmitSurveyData
{
    return new SubmitSurveyData(
        periodId: $fixture->period->id,
        respondentId: $fixture->respondent->id,
        sessionId: $fixture->session->id,
        unitId: $fixture->unit->id,
        idempotencyKey: $idempotencyKey ?? (string) Str::uuid(),
        instrumentVersion: $fixture->period->instrument_version,
        startedAt: CarbonImmutable::now()->subMinutes(4),
        answers: array_fill_keys(range(1, 26), 4),
    );
}

function dashboardFixture(int $uniqueRespondents, array $submissions): object
{
    foreach ($submissions as $code => $count) {
        if ($count < 0 || $count > $uniqueRespondents) {
            throw new InvalidArgumentException("Jumlah submission {$code} tidak valid untuk fixture.");
        }
    }

    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
    ]);

    $respondents = collect(range(1, $uniqueRespondents))->map(function () use ($period) {
        $issued = app(SurveyTokenService::class)->issue();
        $profile = RespondentProfile::factory()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $issued->respondent->id,
            'eligible' => true,
        ]);
        $session = SurveySession::factory()->create([
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $issued->respondent->id,
        ]);

        return (object) ['respondent' => $issued->respondent, 'profile' => $profile, 'session' => $session];
    });

    collect($submissions)->keys()->values()->each(function (string $code, int $index) use ($period, $respondents, $submissions) {
        $unit = EvaluationUnit::factory()->create([
            'code' => $code,
            'name' => Str::headline($code),
            'display_order' => $index + 1,
        ]);
        foreach (range(0, $submissions[$code] - 1) as $respondentIndex) {
            $owner = $respondents[$respondentIndex];
            SurveySubmission::factory()->create([
                'evaluation_period_id' => $period->id,
                'anonymous_respondent_id' => $owner->respondent->id,
                'survey_session_id' => $owner->session->id,
                'evaluation_unit_id' => $unit->id,
            ]);
        }
    });

    return (object) ['period' => $period, 'respondents' => $respondents];
}

function completedSubmissionFixture(): object
{
    $fixture = surveyFixture();
    $fixture->submission = app(SubmitSurvey::class)->handle(validSubmitSurveyData($fixture));

    return $fixture;
}
