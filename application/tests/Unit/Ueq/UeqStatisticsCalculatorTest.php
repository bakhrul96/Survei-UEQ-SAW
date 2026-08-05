<?php

use App\Domain\Ueq\UeqStatisticsCalculator;

function ueqGoldenFixture(): array
{
    return json_decode(
        (string) file_get_contents(__DIR__.'/../../Fixtures/ueq-saw-golden.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
}

function includedAnswersForUnit(array $fixture, string $unit): array
{
    return array_map(
        fn (array $submission): array => $submission['answers'],
        array_values(array_filter(
            $fixture['submissions'],
            fn (array $submission): bool => $submission['unit'] === $unit && $submission['decision'] === 'included',
        )),
    );
}

it('matches all golden UEQ scale statistics for included answers', function (string $unit): void {
    $fixture = ueqGoldenFixture();
    $calculator = app(UeqStatisticsCalculator::class);

    foreach ($fixture['expected']['ueq'][$unit] as $scale => $expected) {
        $result = $calculator->forScale($fixture['items'], includedAnswersForUnit($fixture, $unit), $scale);

        expect($result->n)->toBe($expected['n'])
            ->and($result->unavailableReason)->toBeNull()
            ->and($result->mean)->toEqualWithDelta($expected['mean'], $fixture['tolerance'])
            ->and($result->standardDeviation)->toEqualWithDelta($expected['standard_deviation'], $fixture['tolerance'])
            ->and($result->standardError)->toEqualWithDelta($expected['standard_error'], $fixture['tolerance'])
            ->and($result->ci95Lower)->toEqualWithDelta($expected['ci95_lower'], $fixture['tolerance'])
            ->and($result->ci95Upper)->toEqualWithDelta($expected['ci95_upper'], $fixture['tolerance'])
            ->and($result->cronbachAlpha)->toEqualWithDelta($expected['alpha'], $fixture['tolerance']);
    }
})->with(['ibadah-yu', 'info-yu']);

it('marks a scale with fewer than two included responses unavailable', function (): void {
    $fixture = ueqGoldenFixture();
    $answers = [includedAnswersForUnit($fixture, 'ibadah-yu')[0]];

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->n)->toBe(1)
        ->and($result->mean)->toBeNull()
        ->and($result->standardDeviation)->toBeNull()
        ->and($result->standardError)->toBeNull()
        ->and($result->ci95Lower)->toBeNull()
        ->and($result->ci95Upper)->toBeNull()
        ->and($result->cronbachAlpha)->toBeNull()
        ->and($result->unavailableReason)->toBe('n_below_2');
});

it('marks a scale with no response variation unavailable instead of using zero statistics', function (): void {
    $fixture = ueqGoldenFixture();
    $answers = array_fill(0, 2, array_fill_keys(range(1, 26), 4));

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->n)->toBe(2)
        ->and($result->mean)->toBeNull()
        ->and($result->standardDeviation)->toBeNull()
        ->and($result->standardError)->toBeNull()
        ->and($result->ci95Lower)->toBeNull()
        ->and($result->ci95Upper)->toBeNull()
        ->and($result->cronbachAlpha)->toBeNull()
        ->and($result->unavailableReason)->toBe('zero_variance');
});
