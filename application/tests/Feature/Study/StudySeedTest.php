<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Database\Seeders\WongReangStudySeeder;

it('seeds the fixed Wong Reang study exactly once', function () {
    $this->seed(WongReangStudySeeder::class);
    $this->seed(WongReangStudySeeder::class);

    expect(EvaluationPeriod::count())->toBe(1)
        ->and(EvaluationPeriod::first()->status)->toBe(PeriodStatus::Draft)
        ->and(EvaluationUnit::count())->toBe(13)
        ->and(UeqItem::count())->toBe(26)
        ->and(UeqBenchmark::count())->toBe(6);

    expect(UeqItem::query()->where('order', 1)->firstOrFail()->positive_pole)->toBe('right')
        ->and(UeqItem::query()->where('order', 3)->firstOrFail()->positive_pole)->toBe('left');
});
