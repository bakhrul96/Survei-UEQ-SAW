<?php

namespace App\Application\Reporting;

use App\Models\EvaluationPeriod;
use Carbon\CarbonInterface;

final class AggregateCsvExport
{
    public function __construct(
        private readonly AggregateReportQuery $query = new AggregateReportQuery,
    ) {}

    /** @return iterable<list<string|int|float|null>> */
    public function rows(EvaluationPeriod $period, CarbonInterface $generatedAt): iterable
    {
        yield [
            'section', 'period_name', 'instrument_version', 'benchmark_version', 'benchmark_source',
            'run_id', 'run_status', 'generated_at', 'unit_code', 'unit_name', 'scale', 'scenario',
            'metric', 'value', 'rank', 'delta_rank', 'reason',
        ];

        $data = $this->query->for($period);
        $run = $data->selectedRun;
        $base = [
            'period_name' => $period->name,
            'instrument_version' => $period->instrument_version,
            'run_id' => $run?->id,
            'run_status' => $run?->status,
            'generated_at' => $generatedAt->toIso8601String(),
        ];

        $metadata = [
            'period_slug' => $period->slug,
            'algorithm_version' => $run?->algorithm_version,
            'input_hash' => $run?->input_hash,
            'included_count' => $run?->included_count,
            'excluded_count' => $run?->excluded_count,
            'calculated_at' => $run?->calculated_at?->toIso8601String(),
            'official_locked_at' => $run?->official_locked_at?->toIso8601String(),
            'minimum_deviation_reason' => $run?->minimum_deviation_reason,
            'minimum_deviation_approval_reference' => $run?->minimum_deviation_approval_reference,
        ];
        foreach ($metadata as $metric => $value) {
            yield $this->row('metadata', $base, metric: $metric, value: $value);
        }

        foreach ($data->benchmarks as $benchmark) {
            if (! is_array($benchmark)) {
                continue;
            }
            yield $this->row(
                'benchmark',
                $base,
                benchmarkVersion: (string) ($benchmark['version'] ?? ''),
                benchmarkSource: (string) ($benchmark['source'] ?? ''),
                scale: (string) ($benchmark['scale'] ?? ''),
                metric: 'good_threshold',
                value: $this->scalar($benchmark['good_threshold'] ?? null),
            );
            yield $this->row(
                'benchmark',
                $base,
                benchmarkVersion: (string) ($benchmark['version'] ?? ''),
                benchmarkSource: (string) ($benchmark['source'] ?? ''),
                scale: (string) ($benchmark['scale'] ?? ''),
                metric: 'verified_at',
                value: $this->scalar($benchmark['verified_at'] ?? null),
            );
        }

        $ueqMetrics = [
            'n' => 'n', 'mean' => 'mean', 'sd' => 'standard_deviation', 'se' => 'standard_error',
            'ci95_lower' => 'ci95_lower', 'ci95_upper' => 'ci95_upper', 'alpha' => 'cronbach_alpha', 'gap' => 'gap',
        ];
        foreach ($data->ueqSummary as $unit) {
            if (! is_array($unit) || ! is_array($unit['scales'] ?? null)) {
                continue;
            }
            foreach ($unit['scales'] as $scale => $values) {
                if (! is_string($scale) || ! is_array($values)) {
                    continue;
                }
                $benchmark = $data->benchmarks->firstWhere('scale', $scale);
                foreach ($ueqMetrics as $metric => $key) {
                    yield $this->row(
                        'ueq',
                        $base,
                        benchmarkVersion: is_array($benchmark) ? (string) ($benchmark['version'] ?? '') : '',
                        benchmarkSource: is_array($benchmark) ? (string) ($benchmark['source'] ?? '') : '',
                        unitCode: (string) ($unit['unit_code'] ?? ''),
                        unitName: (string) ($unit['unit_name'] ?? ''),
                        scale: $scale,
                        metric: $metric,
                        value: $this->scalar($values[$key] ?? null),
                    );
                }
            }
        }

        $sawMetrics = [
            'x1_gap', 'x2_days', 'x3_urgency', 'r1', 'r2', 'r3',
            'contribution_c1', 'contribution_c2', 'contribution_c3', 'vi', 'rank', 'is_tied',
        ];
        foreach ($data->sawRanking as $unit) {
            if (! is_array($unit)) {
                continue;
            }
            foreach ($sawMetrics as $metric) {
                $value = $metric === 'is_tied' ? ((bool) ($unit[$metric] ?? false) ? 1 : 0) : ($unit[$metric] ?? null);
                yield $this->row(
                    'saw',
                    $base,
                    unitCode: (string) ($unit['unit_code'] ?? ''),
                    unitName: (string) ($unit['unit_name'] ?? ''),
                    metric: $metric,
                    value: $this->scalar($value),
                    rank: is_numeric($unit['rank'] ?? null) ? (int) $unit['rank'] : null,
                );
            }
        }

        $snapshot = $run?->getAttribute('input_snapshot');
        $scenarioWeights = is_array($snapshot)
            && is_array($snapshot['configuration'] ?? null)
            && is_array($snapshot['configuration']['sensitivity_scenarios'] ?? null)
                ? $snapshot['configuration']['sensitivity_scenarios']
                : [];
        foreach ($scenarioWeights as $scenario => $weights) {
            if (! is_string($scenario) || ! is_array($weights)) {
                continue;
            }
            foreach (['c1', 'c2', 'c3'] as $criterion) {
                yield $this->row('sensitivity', $base, scenario: $scenario, metric: "weight_{$criterion}", value: $this->scalar($weights[$criterion] ?? null));
            }
        }
        foreach ($data->sensitivityMatrix as $unit) {
            if (! is_array($unit) || ! is_array($unit['scenarios'] ?? null)) {
                continue;
            }
            foreach ($unit['scenarios'] as $scenario => $values) {
                if (! is_string($scenario) || ! is_array($values)) {
                    continue;
                }
                foreach (['preference_value', 'rank', 'delta_rank'] as $metric) {
                    yield $this->row(
                        'sensitivity',
                        $base,
                        unitCode: (string) ($unit['unit_code'] ?? ''),
                        unitName: (string) ($unit['unit_name'] ?? ''),
                        scenario: $scenario,
                        metric: $metric,
                        value: $this->scalar($values[$metric] ?? null),
                        rank: is_numeric($values['rank'] ?? null) ? (int) $values['rank'] : null,
                        deltaRank: is_numeric($values['delta_rank'] ?? null) ? (int) $values['delta_rank'] : null,
                    );
                }
            }
        }

        foreach ($data->operationalBacklog as $unit) {
            if (! is_array($unit)) {
                continue;
            }
            foreach (['operational_order', 'decision', 'reviewer_name', 'updated_at'] as $metric) {
                yield $this->row(
                    'operational_backlog',
                    $base,
                    unitCode: (string) ($unit['unit_code'] ?? ''),
                    unitName: (string) ($unit['unit_name'] ?? ''),
                    metric: $metric,
                    value: $this->scalar($unit[$metric] ?? null),
                    rank: is_numeric($unit['operational_order'] ?? null) ? (int) $unit['operational_order'] : null,
                    reason: (string) ($unit['reason'] ?? ''),
                );
            }
        }
    }

    /**
     * @param  array{period_name: string, instrument_version: string, run_id: int|null, run_status: string|null, generated_at: string}  $base
     * @return list<string|int|float|null>
     */
    private function row(
        string $section,
        array $base,
        string $benchmarkVersion = '',
        string $benchmarkSource = '',
        string $unitCode = '',
        string $unitName = '',
        string $scale = '',
        string $scenario = '',
        string $metric = '',
        string|int|float|null $value = null,
        ?int $rank = null,
        ?int $deltaRank = null,
        string $reason = '',
    ): array {
        return [
            $section, $base['period_name'], $base['instrument_version'], $benchmarkVersion, $benchmarkSource,
            $base['run_id'], $base['run_status'], $base['generated_at'], $unitCode, $unitName, $scale,
            $scenario, $metric, $value, $rank, $deltaRank, $reason,
        ];
    }

    private function scalar(mixed $value): string|int|float|null
    {
        return is_string($value) || is_int($value) || is_float($value) || $value === null
            ? $value
            : (string) $value;
    }
}
