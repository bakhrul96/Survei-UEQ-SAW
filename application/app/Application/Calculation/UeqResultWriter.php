<?php

namespace App\Application\Calculation;

use App\Domain\Ueq\UeqScaleStatistics;
use App\Domain\Ueq\UeqStatisticsCalculator;
use App\Models\CalculationRun;

class UeqResultWriter
{
    public function __construct(private readonly UeqStatisticsCalculator $statistics) {}

    /** @param array<string, mixed> $snapshot
     * @return array{rows: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    public function calculate(array $snapshot): array
    {
        $answersByUnit = [];
        foreach ($snapshot['quality_decisions'] as $decision) {
            if ($decision['decision'] !== 'included') {
                continue;
            }

            $answersByUnit[$decision['evaluation_unit_id']][] = $snapshot['included_raw_answers'][(string) $decision['submission_id']];
        }

        $thresholds = [];
        foreach ($snapshot['benchmarks'] as $benchmark) {
            $thresholds[$benchmark['scale']] = (float) $benchmark['good_threshold'];
        }

        $scales = [];
        foreach ($snapshot['items'] as $item) {
            $scales[$item['scale']] = true;
        }
        $scales = array_keys($scales);
        sort($scales, SORT_STRING);
        $rows = [];
        $warnings = $snapshot['warnings'];

        foreach ($snapshot['units'] as $unit) {
            foreach ($scales as $scale) {
                $statistics = $this->statistics->forScale($snapshot['items'], $answersByUnit[$unit['id']] ?? [], $scale);
                $rows[] = $this->row($unit['id'], $scale, $statistics, $thresholds[$scale]);

                if ($statistics->unavailableReason !== null) {
                    $warnings[] = "{$unit['code']} / {$scale}: {$statistics->unavailableReason}";
                }
            }
        }

        return ['rows' => $rows, 'warnings' => array_values(array_unique($warnings))];
    }

    /** @param array<int, array<string, mixed>> $rows */
    public function write(CalculationRun $run, array $rows): void
    {
        foreach ($rows as $row) {
            $run->ueqResults()->create($row);
        }
    }

    /** @return array<string, mixed> */
    private function row(int $unitId, string $scale, UeqScaleStatistics $statistics, float $threshold): array
    {
        return [
            'evaluation_unit_id' => $unitId,
            'scale' => $scale,
            'n' => $statistics->n,
            'mean' => $statistics->mean,
            'standard_deviation' => $statistics->standardDeviation,
            'standard_error' => $statistics->standardError,
            'ci95_lower' => $statistics->ci95Lower,
            'ci95_upper' => $statistics->ci95Upper,
            'cronbach_alpha' => $statistics->cronbachAlpha,
            'gap' => $statistics->mean === null ? null : max(0, $threshold - $statistics->mean),
            'unavailable_reason' => $statistics->unavailableReason,
        ];
    }
}
