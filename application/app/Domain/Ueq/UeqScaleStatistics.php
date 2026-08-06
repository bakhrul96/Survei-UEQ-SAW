<?php

namespace App\Domain\Ueq;

final readonly class UeqScaleStatistics
{
    /** @param list<string> $reliabilityWarnings */
    public function __construct(
        public int $n,
        public ?float $mean,
        public ?float $standardDeviation,
        public ?float $standardError,
        public ?float $ci95Lower,
        public ?float $ci95Upper,
        public ?float $cronbachAlpha,
        public ?string $unavailableReason,
        public ?string $reliabilityUnavailableReason,
        public array $reliabilityWarnings,
    ) {}
}
