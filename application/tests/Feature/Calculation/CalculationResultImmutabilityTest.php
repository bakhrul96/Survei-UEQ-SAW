<?php

use App\Application\Calculation\CalculationRunService;
use Tests\Support\ReleaseTwoFixture;

it('prevents saw and pooled result mutation or deletion', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);
    $sawResult = $run->sawResults->firstOrFail();
    $pooledResult = $run->ueqPooledResults->firstOrFail();

    expect(fn () => $sawResult->update(['preference_value' => 0.0]))
        ->toThrow(LogicException::class, 'SAW results are immutable.')
        ->and(fn () => $sawResult->delete())
        ->toThrow(LogicException::class, 'SAW results are immutable.')
        ->and(fn () => $pooledResult->update(['cronbach_alpha' => 0.0]))
        ->toThrow(LogicException::class, 'UEQ pooled results are immutable.')
        ->and(fn () => $pooledResult->delete())
        ->toThrow(LogicException::class, 'UEQ pooled results are immutable.');
});
