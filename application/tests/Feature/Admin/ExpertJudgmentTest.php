<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Quality\RecordExpertJudgment;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use App\Models\UeqBenchmark;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    $this->admin = User::factory()->create();
    $this->period = EvaluationPeriod::firstOrFail();
    $this->unit = EvaluationUnit::firstOrFail();
    $this->run = app(CalculationRunService::class)->preview($this->period, $this->admin);
});

it('records expert judgment without modifying saw_results', function () {
    $action = app(RecordExpertJudgment::class);

    $judgment = $action->handle(
        run: $this->run,
        unit: $this->unit,
        operationalOrder: 1,
        reason: 'Modul ini penting untuk perbaikan mendesak berdasarkan masukan dinas.',
        reviewer: $this->admin,
    );

    expect(ExpertJudgment::count())->toBe(1)
        ->and($judgment->operational_order)->toBe(1)
        ->and($judgment->reason)->toBe('Modul ini penting untuk perbaikan mendesak berdasarkan masukan dinas.');
});

it('validates required reason and operational order bounds', function () {
    $action = app(RecordExpertJudgment::class);

    expect(fn () => $action->handle(
        run: $this->run,
        unit: $this->unit,
        operationalOrder: 0,
        reason: 'Alasan valid',
        reviewer: $this->admin,
    ))->toThrow(DomainException::class, 'Urutan backlog operasional harus antara 1 sampai 13.');

    expect(fn () => $action->handle(
        run: $this->run,
        unit: $this->unit,
        operationalOrder: 1,
        reason: '   ',
        reviewer: $this->admin,
    ))->toThrow(DomainException::class, 'Alasan keputusan expert judgment wajib diisi.');
});
