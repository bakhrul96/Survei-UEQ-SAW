<?php

namespace App\Domain\Sensitivity;

enum SensitivityScenario: string
{
    case S0 = 'S0';
    case S1 = 'S1';
    case S2 = 'S2';

    public function label(): string
    {
        return match ($this) {
            self::S0 => 'Baseline Informan (S0)',
            self::S1 => 'Dominasi UX (S1)',
            self::S2 => 'Dominasi Pertimbangan Teknis (S2)',
        };
    }

    /**
     * @return array{c1: float, c2: float, c3: float}|null
     */
    public function fixedWeights(): ?array
    {
        return match ($this) {
            self::S0 => null,
            self::S1 => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
            self::S2 => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
        };
    }
}
