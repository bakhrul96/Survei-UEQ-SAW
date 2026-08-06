<?php

use App\Application\Calculation\CalculationRunService;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    $this->admin = User::factory()->create();
    $this->period = EvaluationPeriod::firstOrFail();
});

it('locks a calculation run as official and archives any previous official run', function () {
    $service = app(CalculationRunService::class);
    $run1 = $service->preview($this->period, $this->admin);
    $official1 = $service->lockAsOfficial($run1, $this->admin);

    expect($official1->status)->toBe('official')
        ->and($official1->locked_by)->toBe($this->admin->id)
        ->and($official1->official_locked_at)->not->toBeNull();

    $run2 = $service->preview($this->period, $this->admin);
    $official2 = $service->lockAsOfficial($run2, $this->admin);

    expect($official2->status)->toBe('official')
        ->and($official1->fresh()->status)->toBe('archived');
});

it('rejects locking a stale calculation run', function () {
    $service = app(CalculationRunService::class);
    $run = $service->preview($this->period, $this->admin);
    $run->update(['status' => 'stale']);

    expect(fn () => $service->lockAsOfficial($run, $this->admin))
        ->toThrow(DomainException::class, 'Kalkulasi berstatus stale tidak dapat dikunci sebagai hasil resmi.');
});
