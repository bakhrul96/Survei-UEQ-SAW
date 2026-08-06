<?php

namespace App\Domain\Sensitivity;

use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;
use DomainException;

class SensitivityCalculator
{
    public function __construct(
        private readonly SawCalculator $sawCalculator = new SawCalculator,
    ) {}

    /**
     * @param  list<SawAlternative>  $alternatives
     * @param  array{c1: float, c2: float, c3: float}  $consensusWeights
     * @param  array<string, mixed>  $configuredScenarios
     * @return array<string, list<SensitivityResultData>>
     */
    public function calculate(array $alternatives, array $consensusWeights, array $configuredScenarios): array
    {
        $scenarioWeights = $this->validateConfiguredScenarios($configuredScenarios);
        $s0Results = $this->sawCalculator->rank($alternatives, $consensusWeights);

        $s0Ranks = [];
        foreach ($s0Results as $result) {
            $s0Ranks[$result->alternative->unitId] = $result->rank;
        }

        $scenarios = [
            [SensitivityScenario::S0, $consensusWeights],
            [SensitivityScenario::S1, $scenarioWeights['S1']],
            [SensitivityScenario::S2, $scenarioWeights['S2']],
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

    /**
     * @param  array<string, mixed>  $configuredScenarios
     * @return array{S1: array{c1: float, c2: float, c3: float}, S2: array{c1: float, c2: float, c3: float}}
     */
    private function validateConfiguredScenarios(array $configuredScenarios): array
    {
        if (! isset($configuredScenarios['S1'], $configuredScenarios['S2'])
            || ! is_array($configuredScenarios['S1'])
            || ! is_array($configuredScenarios['S2'])) {
            throw new DomainException('Konfigurasi sensitivitas S1 dan S2 wajib tersedia.');
        }

        return [
            'S1' => $this->validateWeights('S1', $configuredScenarios['S1']),
            'S2' => $this->validateWeights('S2', $configuredScenarios['S2']),
        ];
    }

    /**
     * @param  array<mixed>  $weights
     * @return array{c1: float, c2: float, c3: float}
     */
    private function validateWeights(string $scenario, array $weights): array
    {
        foreach (['c1', 'c2', 'c3'] as $criterion) {
            if (! array_key_exists($criterion, $weights) || ! is_numeric($weights[$criterion])) {
                throw new DomainException("Bobot sensitivitas {$scenario} harus memuat C1, C2, dan C3 numerik.");
            }
        }

        $validated = [
            'c1' => (float) $weights['c1'],
            'c2' => (float) $weights['c2'],
            'c3' => (float) $weights['c3'],
        ];

        if (collect($validated)->contains(fn (float $weight): bool => $weight < 0.0)) {
            throw new DomainException("Bobot sensitivitas {$scenario} tidak boleh negatif.");
        }

        if (abs(array_sum($validated) - 1.0) > 0.000001) {
            throw new DomainException("Bobot sensitivitas {$scenario} harus berjumlah satu.");
        }

        return $validated;
    }
}
