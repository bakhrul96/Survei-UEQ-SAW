<?php

namespace App\Application\Reporting;

use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use Illuminate\Support\Collection;

readonly class AggregateReportData
{
    /**
     * @param  Collection<int, mixed>  $ueqSummary
     * @param  Collection<int, mixed>  $sawRanking
     * @param  Collection<int, mixed>  $sensitivityMatrix
     * @param  Collection<int, mixed>  $operationalBacklog
     */
    public function __construct(
        public EvaluationPeriod $period,
        public ?CalculationRun $officialRun,
        public ?CalculationRun $latestRun,
        public Collection $ueqSummary,
        public Collection $sawRanking,
        public Collection $sensitivityMatrix,
        public Collection $operationalBacklog,
    ) {}
}
