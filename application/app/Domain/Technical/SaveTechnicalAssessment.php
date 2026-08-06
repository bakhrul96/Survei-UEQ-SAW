<?php

namespace App\Domain\Technical;

use App\Application\Calculation\CalculationInputChangeRecorder;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalInformant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SaveTechnicalAssessment
{
    public function __construct(private readonly CalculationInputChangeRecorder $inputChangeRecorder) {}

    /**
     * @param  array<int, mixed>  $assessments
     * @param  array<string, mixed>  $weights
     */
    public function handle(
        EvaluationPeriod $period,
        string $anonymousCode,
        array $assessments,
        array $weights,
        User $actor,
    ): TechnicalInformant {
        $anonymousCode = trim($anonymousCode);
        if ($anonymousCode === '' || mb_strlen($anonymousCode) > 100) {
            throw new DomainException('Kode anonim informan wajib diisi dan maksimal 100 karakter.');
        }

        $fixedUnitIds = EvaluationUnit::query()->forWongReang()->orderBy('display_order')->pluck('id')->map(fn (int $id): int => $id)->all();
        $providedUnitIds = array_map('intval', array_keys($assessments));
        sort($fixedUnitIds);
        sort($providedUnitIds);
        if ($providedUnitIds !== $fixedUnitIds || count($fixedUnitIds) !== 13) {
            throw new DomainException('Setiap informan harus menilai tepat 13 modul Wong Reang.');
        }

        $validatedAssessments = [];
        foreach ($assessments as $unitId => $assessment) {
            if (! is_array($assessment) || ! array_key_exists('days', $assessment) || ! is_numeric($assessment['days']) || (float) $assessment['days'] <= 0) {
                throw new DomainException('Estimasi hari harus lebih dari nol.');
            }
            if (! array_key_exists('urgency', $assessment) || ! is_int($assessment['urgency']) || $assessment['urgency'] < 1 || $assessment['urgency'] > 5) {
                throw new DomainException('Urgensi arsitektur harus bilangan bulat 1 sampai 5.');
            }

            $validatedAssessments[(int) $unitId] = [
                'days' => (float) $assessment['days'],
                'urgency' => $assessment['urgency'],
            ];
        }

        $weightKeys = array_keys($weights);
        sort($weightKeys);
        if ($weightKeys !== ['c1', 'c2', 'c3']) {
            throw new DomainException('Bobot harus memuat C1, C2, dan C3.');
        }
        foreach ($weights as $weight) {
            if (! is_int($weight) || $weight < 0 || $weight > 100) {
                throw new DomainException('Setiap bobot harus bilangan bulat 0 sampai 100.');
            }
        }
        if (array_sum($weights) !== 100) {
            throw new DomainException('Total bobot C1, C2, dan C3 harus tepat 100 poin.');
        }

        return DB::transaction(function () use ($period, $anonymousCode, $validatedAssessments, $weights, $actor, $fixedUnitIds): TechnicalInformant {
            $lockedPeriod = EvaluationPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $informant = TechnicalInformant::query()
                ->where('evaluation_period_id', $period->id)
                ->where('anonymous_code', $anonymousCode)
                ->with(['assessments', 'criteriaWeight'])
                ->first();

            if ($informant === null && TechnicalInformant::query()->where('evaluation_period_id', $period->id)->count() >= 5) {
                throw new DomainException('Satu periode menerima maksimal lima informan teknis.');
            }

            $oldValues = $informant === null ? null : $this->snapshot($informant);
            $informant ??= TechnicalInformant::query()->create([
                'evaluation_period_id' => $period->id,
                'anonymous_code' => $anonymousCode,
            ]);

            $informant->assessments()->whereNotIn('evaluation_unit_id', $fixedUnitIds)->delete();
            foreach ($validatedAssessments as $unitId => $assessment) {
                $informant->assessments()->updateOrCreate(
                    ['evaluation_unit_id' => $unitId],
                    [
                        'estimated_days' => (float) $assessment['days'],
                        'architecture_urgency' => $assessment['urgency'],
                    ],
                );
            }

            $informant->criteriaWeight()->updateOrCreate([], [
                'c1_points' => $weights['c1'],
                'c2_points' => $weights['c2'],
                'c3_points' => $weights['c3'],
            ]);
            $informant = $informant->fresh(['assessments', 'criteriaWeight']);

            $this->inputChangeRecorder->record(
                $lockedPeriod,
                $actor,
                'technical_assessment.updated',
                TechnicalInformant::class,
                $informant->id,
                $oldValues,
                $this->snapshot($informant),
            );

            return $informant;
        });
    }

    /** @return array<string, mixed> */
    private function snapshot(TechnicalInformant $informant): array
    {
        $informant->loadMissing(['assessments', 'criteriaWeight']);

        return [
            'anonymous_code' => $informant->anonymous_code,
            'assessments' => $informant->assessments
                ->sortBy('evaluation_unit_id')
                ->values()
                ->map(fn ($row): array => [
                    'evaluation_unit_id' => $row->evaluation_unit_id,
                    'estimated_days' => $row->estimated_days,
                    'architecture_urgency' => $row->architecture_urgency,
                ])->all(),
            'weights' => $informant->criteriaWeight === null ? null : [
                'c1' => $informant->criteriaWeight->c1_points,
                'c2' => $informant->criteriaWeight->c2_points,
                'c3' => $informant->criteriaWeight->c3_points,
            ],
        ];
    }
}
