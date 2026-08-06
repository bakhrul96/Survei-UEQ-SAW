<?php

namespace App\Domain\Technical;

final readonly class TechnicalUnitConsensus
{
    public function __construct(
        public int $unitId,
        public int $n,
        public ?float $meanDays,
        public ?float $standardDeviationDays,
        public ?float $meanUrgency,
        public ?float $standardDeviationUrgency,
    ) {}

    /** @return array{unit_id: int, n: int, mean_days: float|null, standard_deviation_days: float|null, mean_urgency: float|null, standard_deviation_urgency: float|null} */
    public function toArray(): array
    {
        return [
            'unit_id' => $this->unitId,
            'n' => $this->n,
            'mean_days' => $this->meanDays,
            'standard_deviation_days' => $this->standardDeviationDays,
            'mean_urgency' => $this->meanUrgency,
            'standard_deviation_urgency' => $this->standardDeviationUrgency,
        ];
    }
}
