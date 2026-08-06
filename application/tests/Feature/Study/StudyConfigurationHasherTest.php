<?php

use App\Domain\Study\StudyConfigurationHasher;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\UeqBenchmark;
use App\Models\UeqItem;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    UeqBenchmark::query()->update(['verified_at' => now()]);
});

it('produces a deterministic SHA-256 hash for the same study configuration', function () {
    $period = EvaluationPeriod::firstOrFail();
    $hasher = app(StudyConfigurationHasher::class);

    expect($hasher->hash($period))->toBe($hasher->hash($period))
        ->and($hasher->hash($period))->toMatch('/^[a-f0-9]{64}$/');
});

it('changes the hash when locked study input changes', function (Closure $mutate) {
    $period = EvaluationPeriod::firstOrFail();
    $hasher = app(StudyConfigurationHasher::class);
    $before = $hasher->hash($period);

    $mutate($period);

    expect($hasher->hash($period->fresh()))->not->toBe($before);
})->with([
    'target' => [fn (EvaluationPeriod $period) => $period->update(['target_per_unit' => $period->target_per_unit + 1])],
    'unit name' => [fn () => EvaluationUnit::query()->firstOrFail()->update(['name' => 'Nama modul berubah'])],
    'item polarity' => [fn () => UeqItem::query()->firstOrFail()->update(['positive_pole' => 'left'])],
    'benchmark threshold' => [fn () => UeqBenchmark::query()->firstOrFail()->update(['good_threshold' => 9.9999])],
]);
