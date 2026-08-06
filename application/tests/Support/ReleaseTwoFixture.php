<?php

namespace Tests\Support;

use App\Application\Quality\ReviewSubmission;
use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Quality\QualityDecision;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\StudyConfigurationHasher;
use App\Domain\Survey\SurveyTokenService;
use App\Domain\Technical\SaveTechnicalAssessment;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\TechnicalInformant;
use App\Models\UeqBenchmark;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\WongReangStudySeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ReleaseTwoFixture
{
    /** @return array<int, array{days: float, urgency: int}> */
    public static function completeAssessments(float $days = 1.0, int $urgency = 3): array
    {
        return EvaluationUnit::query()
            ->forWongReang()
            ->orderBy('display_order')
            ->get()
            ->mapWithKeys(fn (EvaluationUnit $unit): array => [
                $unit->id => ['days' => $days, 'urgency' => $urgency],
            ])->all();
    }

    /** @param array{c1: int, c2: int, c3: int} $weights */
    public static function saveInformant(
        EvaluationPeriod $period,
        User $actor,
        string $code,
        float $days = 1.0,
        int $urgency = 3,
        array $weights = ['c1' => 40, 'c2' => 30, 'c3' => 30],
    ): TechnicalInformant {
        return app(SaveTechnicalAssessment::class)->handle(
            $period,
            $code,
            self::completeAssessments($days, $urgency),
            $weights,
            $actor,
        );
    }

    /** @return Collection<int, TechnicalInformant> */
    public static function seedInformants(EvaluationPeriod $period, User $actor, int $count): Collection
    {
        return collect(range(1, $count))->map(fn (int $number): TechnicalInformant => self::saveInformant(
            $period,
            $actor,
            sprintf('TI-%02d', $number),
            (float) $number,
            min(5, $number),
        ));
    }

    public static function scenario(): object
    {
        app(WongReangStudySeeder::class)->run();
        $period = EvaluationPeriod::query()->where('slug', 'wong-reang-2026')->firstOrFail();
        $admin = User::factory()->withTwoFactor()->create();

        UeqBenchmark::query()
            ->where('version', $period->instrument_version)
            ->update(['verified_at' => now()]);
        $period->update([
            'status' => PeriodStatus::Active,
            'opens_at' => now()->subDay(),
            'closes_at' => now()->addDay(),
            'instrument_source' => 'Instrumen UEQ tervalidasi untuk fixture Rilis 2.',
            'instrument_verified_at' => now(),
        ]);
        $period->update([
            'configuration_locked_at' => now(),
            'configuration_hash' => app(StudyConfigurationHasher::class)->hash($period->fresh()),
        ]);
        $period = $period->fresh();

        $answerVectors = [
            [
                1 => 6, 2 => 5, 3 => 2, 4 => 3, 5 => 2, 6 => 6, 7 => 6, 8 => 5, 9 => 2, 10 => 2,
                11 => 6, 12 => 2, 13 => 5, 14 => 5, 15 => 5, 16 => 5, 17 => 3, 18 => 3, 19 => 3, 20 => 6,
                21 => 3, 22 => 5, 23 => 3, 24 => 2, 25 => 3, 26 => 5,
            ],
            [
                1 => 4, 2 => 6, 3 => 4, 4 => 4, 5 => 4, 6 => 4, 7 => 4, 8 => 3, 9 => 4, 10 => 4,
                11 => 4, 12 => 4, 13 => 4, 14 => 4, 15 => 4, 16 => 4, 17 => 4, 18 => 4, 19 => 4, 20 => 4,
                21 => 4, 22 => 4, 23 => 4, 24 => 4, 25 => 4, 26 => 4,
            ],
        ];

        $units = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->limit(2)->get();
        foreach ($units as $unit) {
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

        self::seedInformants($period, $admin, 3);

        return (object) ['period' => $period->fresh(), 'admin' => $admin];
    }
}
