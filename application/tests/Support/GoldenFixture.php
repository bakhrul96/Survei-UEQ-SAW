<?php

namespace Tests\Support;

use App\Application\Calculation\CalculationRunService;
use App\Application\Quality\ReviewSubmission;
use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Quality\QualityDecision;
use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;
use App\Domain\Saw\SawResultData;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\StudyConfigurationHasher;
use App\Domain\Survey\SurveyTokenService;
use App\Domain\Technical\SaveTechnicalAssessment;
use App\Domain\Ueq\UeqStatisticsCalculator;
use App\Domain\Ueq\UeqTransformer;
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
use JsonException;

final class GoldenFixture
{
    /** @return array<string, mixed> */
    public static function data(): array
    {
        try {
            return json_decode(
                (string) file_get_contents(__DIR__.'/../Fixtures/ueq-saw-golden.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new \RuntimeException('Golden JSON fixture tidak valid.', previous: $exception);
        }
    }

    /** @return list<SawResultData> */
    public static function sawRows(): array
    {
        $fixture = self::data();
        $statistics = new UeqStatisticsCalculator(new UeqTransformer);
        $alternatives = [];

        foreach (['ibadah-yu', 'info-yu'] as $unitIndex => $unitCode) {
            $includedAnswers = array_map(
                fn (array $submission): array => $submission['answers'],
                array_values(array_filter(
                    $fixture['submissions'],
                    fn (array $submission): bool => $submission['unit'] === $unitCode && $submission['decision'] === 'included',
                )),
            );
            $gaps = [];
            foreach ($fixture['benchmarks'] as $scale => $threshold) {
                $result = $statistics->forScale($fixture['items'], $includedAnswers, $scale);
                $gaps[] = max(0.0, (float) $threshold - (float) $result->mean);
            }

            $technicalRows = array_map(
                fn (array $informant): array => $informant['assessments'][$unitCode],
                $fixture['technical_informants'],
            );
            $alternatives[] = new SawAlternative(
                unitCode: $unitCode,
                unitId: $unitIndex + 1,
                gap: array_sum($gaps) / count($gaps),
                meanDays: array_sum(array_column($technicalRows, 'days')) / count($technicalRows),
                meanUrgency: array_sum(array_column($technicalRows, 'urgency')) / count($technicalRows),
            );
        }

        $weights = ['c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0];
        foreach ($fixture['technical_informants'] as $informant) {
            foreach ($weights as $criterion => $value) {
                $weights[$criterion] += $informant['weights'][$criterion] / 100;
            }
        }
        foreach ($weights as $criterion => $value) {
            $weights[$criterion] = $value / count($fixture['technical_informants']);
        }

        return (new SawCalculator)->rank($alternatives, $weights);
    }

    public static function persistedRun(): CalculationRun
    {
        $fixture = self::data();
        app(WongReangStudySeeder::class)->run();
        $period = EvaluationPeriod::query()->where('slug', 'wong-reang-2026')->firstOrFail();
        $admin = User::factory()->create();

        foreach ($fixture['benchmarks'] as $scale => $threshold) {
            UeqBenchmark::query()->updateOrCreate(
                ['version' => $period->instrument_version, 'scale' => $scale],
                [
                    'good_threshold' => $threshold,
                    'source' => 'Golden workbook ueq-saw-v1',
                    'verified_at' => now(),
                ],
            );
        }
        $period->update([
            'status' => PeriodStatus::Active,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
            'instrument_source' => 'UEQ-ID-26-v1 golden fixture',
            'instrument_verified_at' => now(),
        ]);
        $period->update([
            'configuration_locked_at' => now(),
            'configuration_hash' => app(StudyConfigurationHasher::class)->hash($period->fresh()),
        ]);
        $period = $period->fresh();
        $units = EvaluationUnit::query()->forWongReang()->get()->keyBy('code');

        foreach ($fixture['submissions'] as $fixtureSubmission) {
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
                unitId: $units[$fixtureSubmission['unit']]->id,
                idempotencyKey: (string) Str::uuid(),
                instrumentVersion: $period->instrument_version,
                startedAt: CarbonImmutable::now()->subMinutes(4),
                answers: $fixtureSubmission['answers'],
            ));
            $decision = QualityDecision::from($fixtureSubmission['decision']);
            app(ReviewSubmission::class)->handle(
                $submission,
                $admin,
                $decision,
                $decision === QualityDecision::Excluded ? 'Excluded by golden fixture.' : null,
            );
        }

        foreach ($fixture['technical_informants'] as $fixtureInformant) {
            $assessments = EvaluationUnit::query()
                ->forWongReang()
                ->orderBy('display_order')
                ->get()
                ->mapWithKeys(function (EvaluationUnit $unit) use ($fixtureInformant): array {
                    $assessment = $fixtureInformant['assessments'][$unit->code] ?? ['days' => 1.0, 'urgency' => 1];

                    return [$unit->id => [
                        'days' => (float) $assessment['days'],
                        'urgency' => (int) $assessment['urgency'],
                    ]];
                })->all();
            app(SaveTechnicalAssessment::class)->handle(
                $period,
                $fixtureInformant['code'],
                $assessments,
                $fixtureInformant['weights'],
                $admin,
            );
        }

        return app(CalculationRunService::class)
            ->preview($period->fresh(), $admin)
            ->load(['ueqResults.unit', 'ueqPooledResults', 'sawResults.unit', 'sensitivityResults.evaluationUnit']);
    }

    public static function persistedClosedRun(): CalculationRun
    {
        $run = self::persistedRun();
        $run->period->update(['status' => PeriodStatus::Closed]);

        return $run->fresh(['period', 'creator', 'ueqResults.unit', 'ueqPooledResults', 'sawResults.unit', 'sensitivityResults.evaluationUnit']);
    }
}
