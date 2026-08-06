<?php

namespace App\Application\Reporting;

use App\Models\CalculationRun;
use App\Models\EvaluationUnit;
use App\Models\SensitivityResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use LogicException;

final class SensitivityComparisonQuery
{
    public function forRun(CalculationRun $run): SensitivityComparisonData
    {
        $run->loadMissing('sensitivityResults.evaluationUnit');
        $rows = $run->sensitivityResults
            ->groupBy('evaluation_unit_id')
            ->map(function (EloquentCollection $results): array {
                $first = $results->first();
                if (! $first instanceof SensitivityResult || ! $first->evaluationUnit instanceof EvaluationUnit) {
                    throw new LogicException('Hasil sensitivitas tersimpan tanpa relasi unit yang valid.');
                }

                return [
                    'unit_id' => $first->evaluation_unit_id,
                    'unit_code' => $first->evaluationUnit->code,
                    'unit_name' => $first->evaluationUnit->name,
                    'scenarios' => $results->mapWithKeys(fn (SensitivityResult $result): array => [
                        $result->scenario->value => [
                            'preference_value' => $result->preference_value,
                            'rank' => $result->rank,
                            'delta_rank' => $result->delta_rank,
                            'is_tied' => $result->is_tied,
                        ],
                    ])->all(),
                ];
            })
            ->sortBy('unit_code')
            ->values();

        $topThreeSets = [];
        foreach (['S0', 'S1', 'S2'] as $scenario) {
            $topThreeSets[$scenario] = array_values($run->sensitivityResults
                ->filter(fn (SensitivityResult $result): bool => $result->scenario->value === $scenario && $result->rank <= 3)
                ->pluck('evaluation_unit_id')
                ->map(fn (mixed $unitId): int => (int) $unitId)
                ->unique()
                ->sort()
                ->values()
                ->all());
        }

        $topThreeStable = [
            'S1' => $topThreeSets['S1'] === $topThreeSets['S0'],
            'S2' => $topThreeSets['S2'] === $topThreeSets['S0'],
        ];
        $changed = [
            'S1' => $this->symmetricDifference($topThreeSets['S0'], $topThreeSets['S1']),
            'S2' => $this->symmetricDifference($topThreeSets['S0'], $topThreeSets['S2']),
        ];

        return new SensitivityComparisonData($rows, $topThreeStable, $changed);
    }

    /**
     * @param  list<int>  $left
     * @param  list<int>  $right
     * @return list<int>
     */
    private function symmetricDifference(array $left, array $right): array
    {
        $difference = array_values(array_unique([...array_diff($left, $right), ...array_diff($right, $left)]));
        sort($difference);

        return $difference;
    }
}
