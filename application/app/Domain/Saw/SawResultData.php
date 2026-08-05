<?php

namespace App\Domain\Saw;

readonly class SawResultData
{
    public function __construct(
        public SawAlternative $alternative,
        public float $r1,
        public float $r2,
        public float $r3,
        public float $contributionC1,
        public float $contributionC2,
        public float $contributionC3,
        public float $preferenceValue,
        public int $rank,
        public bool $isTied,
    ) {}
}
