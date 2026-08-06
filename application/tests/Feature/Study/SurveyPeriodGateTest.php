<?php

use App\Domain\Study\PeriodStatus;
use App\Domain\Study\SurveyPeriodGate;
use App\Models\EvaluationPeriod;

it('accepts a locked active period inside its configured window', function () {
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'opens_at' => now()->subMinute(),
        'closes_at' => now()->addMinute(),
        'configuration_locked_at' => now(),
    ]);

    expect(app(SurveyPeriodGate::class)->issues($period))->toBe([]);
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
