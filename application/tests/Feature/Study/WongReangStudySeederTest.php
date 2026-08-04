<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Database\Seeders\WongReangStudySeeder;

it('preserves configured and verified records when seeding again', function () {
    $this->seed(WongReangStudySeeder::class);

    $period = EvaluationPeriod::query()->firstOrFail();
    $period->update(['status' => PeriodStatus::Active, 'name' => 'Konfigurasi disetujui', 'instrument_verified_at' => now()]);
    EvaluationUnit::query()->where('code', 'ibadah-yu')->update(['name' => 'Nama disetujui']);
    UeqItem::query()->where('version', $period->instrument_version)->where('order', 1)->update(['left_label' => 'Label disetujui']);
    UeqBenchmark::query()->where('version', $period->instrument_version)->where('scale', 'Novelty')->update(['verified_at' => now(), 'good_threshold' => 9.99]);

    $this->seed(WongReangStudySeeder::class);

    expect($period->fresh()->status)->toBe(PeriodStatus::Active)
        ->and($period->fresh()->name)->toBe('Konfigurasi disetujui')
        ->and($period->fresh()->instrument_verified_at)->not->toBeNull()
        ->and(EvaluationUnit::query()->where('code', 'ibadah-yu')->value('name'))->toBe('Nama disetujui')
        ->and(UeqItem::query()->where('version', $period->instrument_version)->where('order', 1)->value('left_label'))->toBe('Label disetujui')
        ->and(UeqBenchmark::query()->where('version', $period->instrument_version)->where('scale', 'Novelty')->value('verified_at'))->not->toBeNull();
});
