<?php

namespace App\Application\Calculation;

use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;
use App\Models\CalculationRun;
use App\Models\EvaluationUnit;

final class SawResultWriter
{
    public function __construct(private readonly SawCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<int, array<string, mixed>>  $ueqRows
     * @return array{rows: list<array<string, mixed>>, alternatives: list<SawAlternative>, weights: array{c1: float, c2: float, c3: float}, warnings: list<string>}
     */
    public function calculate(array $snapshot, array $ueqRows): array
    {
        $emptyWeights = ['c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0];
        $consensus = $snapshot['technical_consensus'] ?? null;
        if (! is_array($consensus) || ($consensus['is_complete'] ?? false) !== true) {
            return [
                'rows' => [],
                'alternatives' => [],
                'weights' => $emptyWeights,
                'warnings' => ['SAW belum dihitung: konsensus teknis 3–5 informan belum lengkap.'],
            ];
        }

        $rawWeights = $consensus['weights'] ?? null;
        if (! is_array($rawWeights)
            || ! is_numeric($rawWeights['c1'] ?? null)
            || ! is_numeric($rawWeights['c2'] ?? null)
            || ! is_numeric($rawWeights['c3'] ?? null)) {
            return [
                'rows' => [],
                'alternatives' => [],
                'weights' => $emptyWeights,
                'warnings' => ['SAW belum dihitung: bobot konsensus teknis tidak valid.'],
            ];
        }
        $weights = [
            'c1' => (float) $rawWeights['c1'],
            'c2' => (float) $rawWeights['c2'],
            'c3' => (float) $rawWeights['c3'],
        ];

        $consensusByUnit = [];
        $rawUnits = $consensus['units'] ?? null;
        if (is_array($rawUnits)) {
            foreach ($rawUnits as $unit) {
                if (is_array($unit) && is_numeric($unit['unit_id'] ?? null)) {
                    $consensusByUnit[(int) $unit['unit_id']] = $unit;
                }
            }
        }

        $unitIds = array_values(array_unique(array_map(fn (array $row): int => (int) $row['evaluation_unit_id'], $ueqRows)));
        $units = EvaluationUnit::query()->whereIn('id', $unitIds)->get()->keyBy('id');
        $alternatives = [];

        foreach ($unitIds as $unitId) {
            $rows = array_values(array_filter($ueqRows, fn (array $row): bool => (int) $row['evaluation_unit_id'] === $unitId));
            if ($rows === [] || array_filter($rows, fn (array $row): bool => $row['gap'] === null) !== []) {
                continue;
            }

            $summary = $consensusByUnit[$unitId] ?? null;
            if (! is_array($summary)
                || ! is_numeric($summary['mean_days'] ?? null)
                || ! is_numeric($summary['mean_urgency'] ?? null)
                || ! isset($units[$unitId])) {
                continue;
            }

            $alternatives[] = new SawAlternative(
                $units[$unitId]->code,
                $unitId,
                array_sum(array_map(fn (array $row): float => (float) $row['gap'], $rows)) / count($rows),
                (float) $summary['mean_days'],
                (float) $summary['mean_urgency'],
            );
        }

        if (count($alternatives) < 2) {
            return [
                'rows' => [],
                'alternatives' => [],
                'weights' => $weights,
                'warnings' => ['SAW belum dihitung: minimal dua alternatif lengkap diperlukan.'],
            ];
        }

        $rows = array_map(fn ($row): array => [
            'evaluation_unit_id' => $row->alternative->unitId,
            'x1_gap' => $row->alternative->gap,
            'x2_days' => $row->alternative->meanDays,
            'x3_urgency' => $row->alternative->meanUrgency,
            'r1' => $row->r1,
            'r2' => $row->r2,
            'r3' => $row->r3,
            'contribution_c1' => $row->contributionC1,
            'contribution_c2' => $row->contributionC2,
            'contribution_c3' => $row->contributionC3,
            'preference_value' => $row->preferenceValue,
            'rank' => $row->rank,
            'is_tied' => $row->isTied,
        ], $this->calculator->rank($alternatives, $weights));

        return [
            'rows' => $rows,
            'alternatives' => $alternatives,
            'weights' => $weights,
            'warnings' => max(array_map(fn (SawAlternative $alternative): float => $alternative->gap, $alternatives)) === 0.0
                ? ['Semua gap UEQ bernilai nol; normalisasi C1 ditetapkan nol.']
                : [],
        ];
    }

    /** @param list<array<string, mixed>> $rows */
    public function write(CalculationRun $run, array $rows): void
    {
        foreach ($rows as $row) {
            $run->sawResults()->create($row);
        }
    }
}
