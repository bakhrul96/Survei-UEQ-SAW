<?php

namespace App\Domain\Ueq;

use InvalidArgumentException;

final class UeqStatisticsCalculator
{
    private const ZERO_VARIANCE_EPSILON = 1.0E-12;

    /** @var array<int, float> */
    private const T95_BY_DEGREES_OF_FREEDOM = [
        1 => 12.706204736432095,
        2 => 4.302652729696142,
        3 => 3.182446305284263,
        4 => 2.7764451051977987,
        5 => 2.570581835636314,
        6 => 2.4469118511449692,
        7 => 2.3646242510102993,
        8 => 2.306004135204166,
        9 => 2.2621571628540993,
        10 => 2.2281388519649385,
        11 => 2.200985160082949,
        12 => 2.178812829663418,
        13 => 2.160368656461013,
        14 => 2.1447866879169273,
        15 => 2.131449545559323,
        16 => 2.1199052992210112,
        17 => 2.1098155778331806,
        18 => 2.10092204024096,
        19 => 2.093024054408263,
        20 => 2.085963447265837,
        21 => 2.079613844727662,
        22 => 2.073873067904015,
        23 => 2.0686576104190406,
        24 => 2.063898561628021,
        25 => 2.059538552753294,
        26 => 2.055529438642871,
        27 => 2.0518305164802833,
        28 => 2.048407141795244,
        29 => 2.045229642132703,
        30 => 2.0422724563012373,
    ];

    public function __construct(private readonly UeqTransformer $transformer) {}

    /**
     * @param  array<int, array{order: int, scale: string, positive_pole: string}>  $items
     * @param  array<int, array<int|string, int>>  $includedRawAnswers
     */
    public function forScale(array $items, array $includedRawAnswers, string $scale): UeqScaleStatistics
    {
        $scaleItems = array_values(array_filter(
            $items,
            fn (array $item): bool => $item['scale'] === $scale,
        ));

        if (count($scaleItems) < 2) {
            throw new InvalidArgumentException('A UEQ scale requires at least two mapped items.');
        }

        $itemScores = array_fill(0, count($scaleItems), []);
        $respondentMeans = [];
        $totalScores = [];

        foreach ($includedRawAnswers as $answers) {
            $scores = [];

            foreach ($scaleItems as $itemIndex => $item) {
                $order = $item['order'];

                if (! array_key_exists($order, $answers)) {
                    throw new InvalidArgumentException("Missing raw UEQ answer for item {$order}.");
                }

                $score = $this->transformer->score((int) $answers[$order], $item['positive_pole']);
                $scores[] = $score;
                $itemScores[$itemIndex][] = $score;
            }

            $respondentMeans[] = array_sum($scores) / count($scores);
            $totalScores[] = array_sum($scores);
        }

        $n = count($respondentMeans);
        $reliabilityWarnings = $n < 20 ? ['n_below_20'] : [];

        if ($n === 0) {
            return new UeqScaleStatistics(
                $n,
                null,
                null,
                null,
                null,
                null,
                null,
                'n_below_2',
                'n_below_2',
                $reliabilityWarnings,
            );
        }

        $mean = array_sum($respondentMeans) / $n;

        if ($n < 2) {
            return new UeqScaleStatistics(
                $n,
                $mean,
                null,
                null,
                null,
                null,
                null,
                'n_below_2',
                'n_below_2',
                $reliabilityWarnings,
            );
        }

        $meanVariance = $this->sampleVariance($respondentMeans);
        $standardDeviation = sqrt($meanVariance);
        $standardError = $standardDeviation / sqrt($n);
        $criticalT = $this->criticalT95($n - 1);
        $interval = $criticalT * $standardError;
        $totalVariance = $this->sampleVariance($totalScores);
        $cronbachAlpha = null;
        $reliabilityUnavailableReason = null;
        if ($totalVariance <= self::ZERO_VARIANCE_EPSILON) {
            $reliabilityUnavailableReason = 'zero_total_variance';
        } else {
            $itemVarianceSum = array_sum(array_map($this->sampleVariance(...), $itemScores));
            $itemCount = count($scaleItems);
            $cronbachAlpha = ($itemCount / ($itemCount - 1)) * (1 - ($itemVarianceSum / $totalVariance));

            if ($cronbachAlpha < 0.70) {
                $reliabilityWarnings[] = 'alpha_below_0_70';
            }
        }

        return new UeqScaleStatistics(
            $n,
            $mean,
            $standardDeviation,
            $standardError,
            $mean - $interval,
            $mean + $interval,
            $cronbachAlpha,
            null,
            $reliabilityUnavailableReason,
            $reliabilityWarnings,
        );
    }

    /** @param array<int, int|float> $values */
    private function sampleVariance(array $values): float
    {
        $count = count($values);

        if ($count < 2) {
            throw new InvalidArgumentException('Sample variance requires at least two values.');
        }

        $mean = array_sum($values) / $count;
        $squaredDifferenceSum = array_sum(array_map(
            fn (int|float $value): float => ($value - $mean) ** 2,
            $values,
        ));

        return $squaredDifferenceSum / ($count - 1);
    }

    private function criticalT95(int $degreesOfFreedom): float
    {
        return self::T95_BY_DEGREES_OF_FREEDOM[$degreesOfFreedom] ?? 1.959963984540054;
    }
}
