<?php

namespace App\Application\Reporting;

use Illuminate\Support\Collection;

readonly class ReleaseOneDashboardData
{
    /** @param Collection<int, UnitProgressData> $units */
    public function __construct(
        public int $uniqueRespondents,
        public int $totalEvaluations,
        public int $eligibleRespondents,
        public int $flaggedEvaluations,
        public int $excludedEvaluations,
        public int $pendingReviewEvaluations,
        public Collection $units,
    ) {}
}
