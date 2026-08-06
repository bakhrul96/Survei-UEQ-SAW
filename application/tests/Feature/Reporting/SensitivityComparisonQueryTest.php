<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Reporting\SensitivityComparisonQuery;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\SensitivityResult;
use App\Models\User;

it('compares top three sets by rank including ties instead of taking three rows', function (): void {
    $period = EvaluationPeriod::factory()->create();
    $user = User::factory()->create();
    $run = CalculationRun::query()->create([
        'evaluation_period_id' => $period->id,
        'algorithm_version' => CalculationRunService::ALGORITHM_VERSION,
        'status' => 'preview',
        'input_hash' => str_repeat('a', 64),
        'input_snapshot' => [],
        'warnings' => [],
        'included_count' => 0,
        'excluded_count' => 0,
        'created_by' => $user->id,
        'calculated_at' => now(),
    ]);
    $units = EvaluationUnit::factory()->count(4)->create();
    $ranks = [
        'S0' => [1, 2, 3, 4],
        'S1' => [1, 2, 4, 3],
        'S2' => [3, 2, 1, 4],
    ];
    foreach ($ranks as $scenario => $scenarioRanks) {
        foreach ($units as $index => $unit) {
            SensitivityResult::query()->create([
                'calculation_run_id' => $run->id,
                'scenario' => $scenario,
                'evaluation_unit_id' => $unit->id,
                'preference_value' => 1 - ($scenarioRanks[$index] / 10),
                'rank' => $scenarioRanks[$index],
                'delta_rank' => $ranks['S0'][$index] - $scenarioRanks[$index],
                'is_tied' => false,
            ]);
        }
    }

    $comparison = app(SensitivityComparisonQuery::class)->forRun($run);

    expect($comparison->topThreeStable)->toBe([
        'S1' => false,
        'S2' => true,
    ])->and($comparison->changedTopThreeUnitIds['S1'])->toContain($units[2]->id, $units[3]->id)
        ->and($comparison->changedTopThreeUnitIds['S2'])->toBe([]);
});
