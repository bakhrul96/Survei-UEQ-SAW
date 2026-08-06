<?php

namespace App\Application\Calculation;

use App\Domain\Ueq\UeqScaleStatistics;
use App\Domain\Ueq\UeqStatisticsCalculator;
use App\Models\CalculationRun;

final class UeqResultWriter
{
    public function __construct(private readonly UeqStatisticsCalculator $statistics) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{rows: list<array<string, mixed>>, pooledRows: list<array<string, mixed>>, warnings: list<string>}
     */
    public function calculate(array $snapshot): array
    {
        $answersByUnit = [];
        $pooledAnswers = [];
        foreach ($snapshot['quality_decisions'] as $decision) {
            if ($decision['decision'] !== 'included') {
                continue;
            }

            $answers = $snapshot['included_raw_answers'][(string) $decision['submission_id']];
            $answersByUnit[$decision['evaluation_unit_id']][] = $answers;
            $pooledAnswers[] = $answers;
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
        $pooledRows = [];
        $warnings = $snapshot['warnings'];

        foreach ($snapshot['units'] as $unit) {
            foreach ($scales as $scale) {
                $statistics = $this->statistics->forScale($snapshot['items'], $answersByUnit[$unit['id']] ?? [], $scale);
                $rows[] = $this->row($unit['id'], $scale, $statistics, $thresholds[$scale]);

                if ($statistics->unavailableReason !== null) {
                    $warnings[] = "{$unit['code']} / {$scale}: {$statistics->unavailableReason}";
                }
                if ($statistics->reliabilityUnavailableReason !== null) {
                    $warnings[] = "{$unit['code']} / {$scale} / reliability: {$statistics->reliabilityUnavailableReason}";
                }
                foreach ($statistics->reliabilityWarnings as $warning) {
                    $warnings[] = "{$unit['code']} / {$scale} / reliability: {$warning}";
                }
            }
        }

        foreach ($scales as $scale) {
            $statistics = $this->statistics->forScale($snapshot['items'], $pooledAnswers, $scale);
            $pooledWarnings = array_values(array_filter(
                $statistics->reliabilityWarnings,
                fn (string $warning): bool => $warning !== 'n_below_20',
            ));
            $pooledRows[] = [
                'scope' => 'pooled',
                'scale' => $scale,
                'n' => $statistics->n,
                'cronbach_alpha' => $statistics->cronbachAlpha,
                'unavailable_reason' => $statistics->reliabilityUnavailableReason,
                'warnings' => $pooledWarnings,
            ];

            if ($statistics->reliabilityUnavailableReason !== null) {
                $warnings[] = "pooled / {$scale} / reliability: {$statistics->reliabilityUnavailableReason}";
            }
            foreach ($pooledWarnings as $warning) {
                $warnings[] = "pooled / {$scale} / reliability: {$warning}";
            }
        }

        return [
            'rows' => $rows,
            'pooledRows' => $pooledRows,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array<string, mixed>>  $pooledRows
     */
    public function write(CalculationRun $run, array $rows, array $pooledRows): void
    {
        foreach ($rows as $row) {
            $run->ueqResults()->create($row);
        }
        foreach ($pooledRows as $row) {
            $run->ueqPooledResults()->create($row);
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
            'reliability_unavailable_reason' => $statistics->reliabilityUnavailableReason,
            'reliability_warnings' => $statistics->reliabilityWarnings,
            'gap' => $statistics->mean === null ? null : max(0, $threshold - $statistics->mean),
            'unavailable_reason' => $statistics->unavailableReason,
        ];
    }
}
