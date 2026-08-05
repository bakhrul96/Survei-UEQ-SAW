<?php

namespace App\Domain\Technical;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalInformant;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaveTechnicalAssessment
{
    /**
     * @param  array<int, array{days: float|int, urgency: int}>  $assessments
     * @param  array{c1: int, c2: int, c3: int}  $weights
     */
    public function handle(EvaluationPeriod $period, string $anonymousCode, array $assessments, array $weights): TechnicalInformant
    {
        $fixedUnitIds = EvaluationUnit::query()->forWongReang()->pluck('id')->all();

        if (array_diff(array_keys($assessments), $fixedUnitIds) !== []) {
            throw new InvalidArgumentException('Penilaian hanya dapat menggunakan 13 unit Wong Reang yang telah ditetapkan.');
        }

        return DB::transaction(function () use ($period, $anonymousCode, $assessments, $weights): TechnicalInformant {
            $informant = TechnicalInformant::query()->firstOrCreate([
                'evaluation_period_id' => $period->id,
                'anonymous_code' => $anonymousCode,
            ]);

            foreach ($assessments as $unitId => $assessment) {
                $informant->assessments()->updateOrCreate(
                    ['evaluation_unit_id' => $unitId],
                    [
                        'estimated_days' => $assessment['days'],
                        'architecture_urgency' => $assessment['urgency'],
                    ],
                );
            }

            $informant->criteriaWeight()->updateOrCreate([], [
                'c1_points' => $weights['c1'],
                'c2_points' => $weights['c2'],
                'c3_points' => $weights['c3'],
            ]);

            return $informant;
        });
    }
}
