<?php

namespace App\Domain\Sensitivity;

use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;

class SensitivityCalculator
{
    public function __construct(
        private readonly SawCalculator $sawCalculator = new SawCalculator,
    ) {}

    /**
     * @param  list<SawAlternative>  $alternatives
     * @param  array{c1: float, c2: float, c3: float}  $consensusWeights
     * @return array<string, list<SensitivityResultData>>
     */
    public function calculate(array $alternatives, array $consensusWeights): array
    {
        $s0Results = $this->sawCalculator->rank($alternatives, $consensusWeights);

        $s0Ranks = [];
        foreach ($s0Results as $result) {
            $s0Ranks[$result->alternative->unitId] = $result->rank;
        }

        $s1Weights = SensitivityScenario::S1->fixedWeights();
        $s2Weights = SensitivityScenario::S2->fixedWeights();

        $scenarios = [
            SensitivityScenario::S0->value => [
                'enum' => SensitivityScenario::S0,
                'weights' => $consensusWeights,
            ],
            SensitivityScenario::S1->value => [
                'enum' => SensitivityScenario::S1,
                'weights' => $s1Weights ?? ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
            ],
            SensitivityScenario::S2->value => [
                'enum' => SensitivityScenario::S2,
                'weights' => $s2Weights ?? ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
            ],
        ];

        $output = [];

        foreach ($scenarios as $key => $config) {
            /** @var SensitivityScenario $scenarioEnum */
            $scenarioEnum = $config['enum'];
            /** @var array{c1: float, c2: float, c3: float} $weights */
            $weights = $config['weights'];

            $sawResults = $scenarioEnum === SensitivityScenario::S0
                ? $s0Results
                : $this->sawCalculator->rank($alternatives, $weights);

            $scenarioData = [];
            foreach ($sawResults as $result) {
                $unitId = $result->alternative->unitId;
                $s0Rank = $s0Ranks[$unitId] ?? $result->rank;
                $deltaRank = $s0Rank - $result->rank;

                $scenarioData[] = new SensitivityResultData(
                    scenario: $scenarioEnum,
                    evaluationUnitId: $unitId,
                    unitCode: $result->alternative->unitCode,
                    preferenceValue: $result->preferenceValue,
                    rank: $result->rank,
                    deltaRank: $deltaRank,
                    isTied: $result->isTied,
                );
            }

            $output[$key] = $scenarioData;
        }

        return $output;
    }
}
