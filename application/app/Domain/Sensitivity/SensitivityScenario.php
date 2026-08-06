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
}
