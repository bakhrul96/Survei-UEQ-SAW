<?php

namespace App\Domain\Saw;

readonly class SawAlternative
{
    public function __construct(
        public string $unitCode,
        public int $unitId,
        public float $gap,
        public float $meanDays,
        public float $meanUrgency,
    ) {}
}
