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

function rawAnswersForTransformedScore(array $items, int $score): array
{
    return array_reduce($items, function (array $answers, array $item) use ($score): array {
        $answers[$item['order']] = $item['positive_pole'] === 'right' ? $score + 4 : 4 - $score;

        return $answers;
    }, []);
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

it('keeps a one-response mean while marking inferential statistics unavailable', function (): void {
    $fixture = ueqGoldenFixture();
    $answers = [includedAnswersForUnit($fixture, 'ibadah-yu')[0]];

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->n)->toBe(1)
        ->and($result->mean)->not->toBeNull()
        ->and($result->standardDeviation)->toBeNull()
        ->and($result->standardError)->toBeNull()
        ->and($result->ci95Lower)->toBeNull()
        ->and($result->ci95Upper)->toBeNull()
        ->and($result->cronbachAlpha)->toBeNull()
        ->and($result->unavailableReason)->toBe('n_below_2')
        ->and($result->reliabilityUnavailableReason)->toBe('n_below_2')
        ->and($result->reliabilityWarnings)->toContain('n_below_20');
});

it('keeps zero descriptive variance as zero but leaves alpha unavailable', function (): void {
    $fixture = ueqGoldenFixture();
    $answers = array_fill(0, 3, array_fill_keys(range(1, 26), 4));

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect($result->n)->toBe(3)
        ->and($result->mean)->toBe(0.0)
        ->and($result->standardDeviation)->toBe(0.0)
        ->and($result->standardError)->toBe(0.0)
        ->and($result->ci95Lower)->toBe(0.0)
        ->and($result->ci95Upper)->toBe(0.0)
        ->and($result->cronbachAlpha)->toBeNull()
        ->and($result->unavailableReason)->toBeNull()
        ->and($result->reliabilityUnavailableReason)->toBe('zero_total_variance');
});

it('uses scale-specific fixture answers instead of treating all 26 items as one scale', function (string $unit): void {
    $fixture = ueqGoldenFixture();
    $calculator = app(UeqStatisticsCalculator::class);
    $answers = includedAnswersForUnit($fixture, $unit);
    $signatures = [];

    foreach (array_keys($fixture['benchmarks']) as $scale) {
        $result = $calculator->forScale($fixture['items'], $answers, $scale);
        $signatures[] = implode('|', [
            $result->mean,
            $result->standardDeviation,
            $result->ci95Lower,
            $result->ci95Upper,
            $result->cronbachAlpha,
        ]);
    }

    expect(count(array_unique($signatures)))->toBe(6);
})->with(['ibadah-yu', 'info-yu']);

it('uses the documented two-sided critical t at boundary degrees of freedom', function (int $n, float $expectedCriticalT): void {
    $fixture = ueqGoldenFixture();
    $scores = array_merge(array_fill(0, intdiv($n + 1, 2), 1), array_fill(0, intdiv($n, 2), 2));
    $answers = array_map(
        fn (int $score): array => rawAnswersForTransformedScore($fixture['items'], $score),
        $scores,
    );

    $result = app(UeqStatisticsCalculator::class)->forScale($fixture['items'], $answers, 'Attractiveness');

    expect(($result->ci95Upper - $result->mean) / $result->standardError)
        ->toEqualWithDelta($expectedCriticalT, $fixture['tolerance']);
})->with([
    [2, 12.706204736432095],
    [31, 2.0422724563012373],
    [32, 1.959963984540054],
]);
