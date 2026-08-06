<?php

namespace App\Domain\Technical;

final readonly class TechnicalConsensusData
{
    /**
     * @param  list<string>  $incompleteReasons
     * @param  array<int, TechnicalUnitConsensus>  $units
     * @param  array{c1: float|null, c2: float|null, c3: float|null}  $weights
     */
    public function __construct(
        public int $informantCount,
        public bool $isComplete,
        public array $incompleteReasons,
        public array $units,
        public array $weights,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'informant_count' => $this->informantCount,
            'is_complete' => $this->isComplete,
            'incomplete_reasons' => $this->incompleteReasons,
            'units' => array_map(
                fn (TechnicalUnitConsensus $unit): array => $unit->toArray(),
                $this->units,
            ),
            'weights' => $this->weights,
        ];
    }
}
