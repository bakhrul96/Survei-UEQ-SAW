<?php

namespace App\Application\Calculation;

use App\Domain\Sensitivity\SensitivityResultData;
use App\Models\CalculationRun;
use App\Models\SensitivityResult;

class SensitivityResultWriter
{
    /**
     * @param  array<string, list<SensitivityResultData>>  $sensitivityScenarios
     */
    public function write(CalculationRun $run, array $sensitivityScenarios): void
    {
        $run->sensitivityResults()->delete();

        foreach ($sensitivityScenarios as $scenarioResults) {
            foreach ($scenarioResults as $result) {
                SensitivityResult::query()->create([
                    'calculation_run_id' => $run->id,
                    'scenario' => $result->scenario,
                    'evaluation_unit_id' => $result->evaluationUnitId,
                    'preference_value' => $result->preferenceValue,
                    'rank' => $result->rank,
                    'delta_rank' => $result->deltaRank,
                    'is_tied' => $result->isTied,
                ]);
            }
        }
    }
}
