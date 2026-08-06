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

        $scenarios = [
            [SensitivityScenario::S0, SensitivityScenario::S0->resolvedWeights($consensusWeights)],
            [SensitivityScenario::S1, SensitivityScenario::S1->resolvedWeights($consensusWeights)],
            [SensitivityScenario::S2, SensitivityScenario::S2->resolvedWeights($consensusWeights)],
        ];

        $output = [];

        foreach ($scenarios as [$scenarioEnum, $weights]) {
            $key = $scenarioEnum->value;

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
