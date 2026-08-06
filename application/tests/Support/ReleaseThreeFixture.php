<?php

namespace Tests\Support;

use App\Application\Calculation\CalculationRunService;
use App\Application\Quality\ReviewSubmission;
use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Quality\QualityDecision;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\StudyConfigurationHasher;
use App\Domain\Survey\SurveyTokenService;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\UeqBenchmark;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\WongReangStudySeeder;
use Illuminate\Support\Str;
use RuntimeException;

final class ReleaseThreeFixture
{
    /** @return object{period: EvaluationPeriod, admin: User, run: CalculationRun} */
    public static function eligibleScenario(): object
    {
        app(WongReangStudySeeder::class)->run();
        $period = EvaluationPeriod::query()->where('slug', 'wong-reang-2026')->firstOrFail();
        $admin = User::factory()->withTwoFactor()->create();

        UeqBenchmark::query()->where('version', $period->instrument_version)->update(['verified_at' => now()]);
        $period->update([
            'status' => PeriodStatus::Active,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
            'minimum_per_unit' => 2,
            'target_per_unit' => 2,
            'target_basis' => 'Fixture deterministik Rilis 3: dua respons included per modul.',
            'instrument_source' => 'Instrumen UEQ tervalidasi untuk fixture Rilis 3.',
            'instrument_verified_at' => now(),
        ]);
        $period->update([
            'configuration_locked_at' => now(),
            'configuration_hash' => app(StudyConfigurationHasher::class)->hash($period->fresh()),
        ]);
        $period = $period->fresh();

        $answerVectors = [
            array_combine(range(1, 26), [6, 5, 2, 3, 2, 6, 6, 5, 2, 2, 6, 2, 5, 5, 5, 5, 3, 3, 3, 6, 3, 5, 3, 2, 3, 5]),
            array_combine(range(1, 26), [4, 6, 4, 4, 4, 4, 4, 3, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4, 4]),
        ];
        foreach (EvaluationUnit::query()->forWongReang()->orderBy('display_order')->get() as $unit) {
            foreach ($answerVectors as $answers) {
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
                $submission = app(SubmitSurvey::class)->handle(new SubmitSurveyData(
                    periodId: $period->id,
                    respondentId: $issued->respondent->id,
                    sessionId: $session->id,
                    unitId: $unit->id,
                    idempotencyKey: (string) Str::uuid(),
                    instrumentVersion: $period->instrument_version,
                    startedAt: CarbonImmutable::now()->subMinutes(4),
                    answers: $answers,
                ));
                app(ReviewSubmission::class)->handle($submission, $admin, QualityDecision::Included, null);
            }
        }

        ReleaseTwoFixture::seedInformants($period, $admin, 3);
        $period->update(['status' => PeriodStatus::Closed]);
        $run = app(CalculationRunService::class)->preview($period->fresh(), $admin);

        if ($run->sawResults->count() !== 13
            || $run->sensitivityResults->count() !== 39
            || $run->expertJudgments->count() !== 13) {
            throw new RuntimeException('Fixture Rilis 3 tidak menghasilkan output analisis lengkap.');
        }

        return (object) ['period' => $period->fresh(), 'admin' => $admin, 'run' => $run];
    }
}
