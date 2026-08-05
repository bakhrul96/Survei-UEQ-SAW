<?php

namespace App\Domain\Ueq;

final readonly class UeqScaleStatistics
{
    public function __construct(
        public int $n,
        public ?float $mean,
        public ?float $standardDeviation,
        public ?float $standardError,
        public ?float $ci95Lower,
        public ?float $ci95Upper,
        public ?float $cronbachAlpha,
        public ?string $unavailableReason,
    ) {}

    public static function unavailable(int $n, string $reason): self
    {
        return new self($n, null, null, null, null, null, null, $reason);
    }
}
