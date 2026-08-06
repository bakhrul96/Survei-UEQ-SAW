<?php

namespace App\Domain\Sensitivity;

readonly class SensitivityResultData
{
    public function __construct(
        public SensitivityScenario $scenario,
        public int $evaluationUnitId,
        public string $unitCode,
        public float $preferenceValue,
        public int $rank,
        public int $deltaRank,
        public bool $isTied = false,
    ) {}
}
