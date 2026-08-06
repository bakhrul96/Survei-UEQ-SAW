<?php

namespace App\Application\Reporting;

use Illuminate\Support\Collection;

readonly class SensitivityComparisonData
{
    /**
     * @param  Collection<int, mixed>  $rows
     * @param  array{S1: bool, S2: bool}  $topThreeStable
     * @param  array{S1: list<int>, S2: list<int>}  $changedTopThreeUnitIds
     */
    public function __construct(
        public Collection $rows,
        public array $topThreeStable,
        public array $changedTopThreeUnitIds,
    ) {}
}
