<?php

use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Domain\Study\StudyConfigurationHasher;
use App\Domain\Study\SurveyPeriodGate;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use Database\Seeders\WongReangStudySeeder;

it('accepts a locked active period inside its configured window', function () {
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Draft,
        'opens_at' => now()->subMinute(),
        'closes_at' => now()->addMinute(),
    ]);
    $period->update([
        'status' => PeriodStatus::Active,
        'configuration_locked_at' => now(),
        'configuration_hash' => app(StudyConfigurationHasher::class)->hash($period),
    ]);

    expect(app(SurveyPeriodGate::class)->issues($period->fresh()))->toBe([]);
});

it('rejects an active period whose locked configuration was changed', function () {
    $this->seed(WongReangStudySeeder::class);
    $period = EvaluationPeriod::query()->firstOrFail();
    $period->update([
        'opens_at' => now()->subMinute(),
        'closes_at' => now()->addMonth(),
        'instrument_source' => 'UEQ Bahasa Indonesia terverifikasi',
        'instrument_verified_at' => now(),
    ]);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    releaseOneReadyAdminAndEvidence($period);
    $period = app(PeriodReadinessService::class)->activate($period->fresh());

    $period->update(['target_per_unit' => $period->target_per_unit + 1]);

    expect(app(SurveyPeriodGate::class)->issues($period->fresh()))
        ->toContain('Konfigurasi periode berubah setelah dikunci.')
        ->and(fn () => app(SurveyPeriodGate::class)->assertAccepting($period->fresh()))
        ->toThrow(DomainException::class, 'Konfigurasi periode berubah setelah dikunci.');
});

it('rejects a period that cannot currently accept submissions', function (array $attributes, string $message) {
    $period = EvaluationPeriod::factory()->create($attributes);

    expect(app(SurveyPeriodGate::class)->issues($period))->toContain($message)
        ->and(fn () => app(SurveyPeriodGate::class)->assertAccepting($period))
        ->toThrow(DomainException::class, $message);
})->with([
    'draft' => [
        ['status' => PeriodStatus::Draft, 'opens_at' => now()->subMinute(), 'closes_at' => now()->addMinute(), 'configuration_locked_at' => now()],
        'Periode penelitian tidak aktif.',
    ],
    'future' => [
        ['status' => PeriodStatus::Active, 'opens_at' => now()->addMinute(), 'closes_at' => now()->addHour(), 'configuration_locked_at' => now()],
        'Periode penelitian belum dibuka.',
    ],
    'expired' => [
        ['status' => PeriodStatus::Active, 'opens_at' => now()->subHour(), 'closes_at' => now()->subMinute(), 'configuration_locked_at' => now()],
        'Periode penelitian sudah ditutup.',
    ],
    'unlocked' => [
        ['status' => PeriodStatus::Active, 'opens_at' => now()->subMinute(), 'closes_at' => now()->addMinute(), 'configuration_locked_at' => null],
        'Konfigurasi periode belum dikunci.',
    ],
]);
