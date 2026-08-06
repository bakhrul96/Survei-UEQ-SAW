<?php

use App\Application\Calculation\CalculationRunService;
use App\Models\UeqResult;
use Tests\Support\ReleaseTwoFixture;

it('stores six pooled diagnostics and unit reliability warnings', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);

    expect($run->ueqPooledResults)->toHaveCount(6)
        ->and($run->ueqPooledResults->pluck('scope')->unique()->values()->all())->toBe(['pooled'])
        ->and($run->ueqPooledResults->every(fn ($row): bool => $row->n === 4))->toBeTrue()
        ->and($run->ueqResults->every(fn (UeqResult $row): bool => is_array($row->reliability_warnings)))->toBeTrue()
        ->and($run->ueqResults->firstWhere('n', 2)->reliability_warnings)->toContain('n_below_20');
});
