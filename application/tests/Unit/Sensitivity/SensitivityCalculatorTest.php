<?php

use App\Domain\Saw\SawAlternative;
use App\Domain\Sensitivity\SensitivityCalculator;
use App\Domain\Sensitivity\SensitivityScenario;

it('calculates sensitivity scenarios S0, S1, and S2 with rank deltas', function () {
    $alternatives = [
        new SawAlternative(unitCode: 'ibadah-yu', unitId: 1, gap: 0.46, meanDays: 4.0, meanUrgency: 3.0),
        new SawAlternative(unitCode: 'info-yu', unitId: 2, gap: 0.96, meanDays: 8.0, meanUrgency: 5.0),
    ];
    $consensusWeights = ['c1' => 0.24, 'c2' => 0.476667, 'c3' => 0.283333];
    $configuredScenarios = [
        'S1' => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
        'S2' => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
    ];

    $calculator = new SensitivityCalculator;
    $results = $calculator->calculate($alternatives, $consensusWeights, $configuredScenarios);

    expect($results)->toHaveKeys(['S0', 'S1', 'S2'])
        ->and($results['S0'])->toHaveCount(2)
        ->and($results['S1'])->toHaveCount(2)
        ->and($results['S2'])->toHaveCount(2);

    expect($results['S0'][0]->scenario)->toBe(SensitivityScenario::S0)
        ->and($results['S0'][0]->deltaRank)->toBe(0);
});

it('detects rank shifts in UX-heavy vs technical-heavy scenarios', function () {
    $alternatives = [
        new SawAlternative(unitCode: 'modul-a', unitId: 1, gap: 0.90, meanDays: 20.0, meanUrgency: 1.0),
        new SawAlternative(unitCode: 'modul-b', unitId: 2, gap: 0.10, meanDays: 2.0, meanUrgency: 5.0),
    ];
    $consensusWeights = ['c1' => 0.333333, 'c2' => 0.333333, 'c3' => 0.333334];
    $configuredScenarios = [
        'S1' => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
        'S2' => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
    ];

    $calculator = new SensitivityCalculator;
    $results = $calculator->calculate($alternatives, $consensusWeights, $configuredScenarios);

    // In S1 (0.6 C1 gap), modul-a should be ranked #1
    expect($results['S1'][0]->unitCode)->toBe('modul-a')
        ->and($results['S1'][0]->rank)->toBe(1);

    // In S2 (0.2 C1, 0.4 C2 days, 0.4 C3 urgency), modul-b should be ranked #1
    expect($results['S2'][0]->unitCode)->toBe('modul-b')
        ->and($results['S2'][0]->rank)->toBe(1);
});

it('uses the configured S1 and S2 weights instead of enum constants', function (): void {
    $alternatives = [
        new SawAlternative(unitCode: 'modul-a', unitId: 1, gap: 0.90, meanDays: 20.0, meanUrgency: 1.0),
        new SawAlternative(unitCode: 'modul-b', unitId: 2, gap: 0.10, meanDays: 2.0, meanUrgency: 5.0),
    ];

    $results = (new SensitivityCalculator)->calculate(
        $alternatives,
        ['c1' => 0.333333, 'c2' => 0.333333, 'c3' => 0.333334],
        [
            'S1' => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
            'S2' => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20],
        ],
    );

    expect($results['S1'][0]->unitCode)->toBe('modul-b')
        ->and($results['S2'][0]->unitCode)->toBe('modul-a');
});

it('rejects malformed configured scenario weights', function (array $scenarios, string $message): void {
    $alternatives = [
        new SawAlternative(unitCode: 'modul-a', unitId: 1, gap: 0.90, meanDays: 20.0, meanUrgency: 1.0),
        new SawAlternative(unitCode: 'modul-b', unitId: 2, gap: 0.10, meanDays: 2.0, meanUrgency: 5.0),
    ];

    expect(fn () => (new SensitivityCalculator)->calculate(
        $alternatives,
        ['c1' => 0.333333, 'c2' => 0.333333, 'c3' => 0.333334],
        $scenarios,
    ))->toThrow(DomainException::class, $message);
})->with([
    'missing S2' => [
        ['S1' => ['c1' => 0.60, 'c2' => 0.20, 'c3' => 0.20]],
        'Konfigurasi sensitivitas S1 dan S2 wajib tersedia.',
    ],
    'S1 does not total one' => [[
        'S1' => ['c1' => 0.50, 'c2' => 0.20, 'c3' => 0.20],
        'S2' => ['c1' => 0.20, 'c2' => 0.40, 'c3' => 0.40],
    ], 'Bobot sensitivitas S1 harus berjumlah satu.'],
]);
