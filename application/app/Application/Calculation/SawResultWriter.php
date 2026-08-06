<?php

namespace App\Application\Calculation;

use App\Domain\Saw\SawAlternative;
use App\Domain\Saw\SawCalculator;
use App\Models\CalculationRun;
use App\Models\EvaluationUnit;

class SawResultWriter
{
    public function __construct(private readonly SawCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<int, array<string, mixed>>  $ueqRows
     * @return array{rows: list<array<string, mixed>>, alternatives: list<SawAlternative>, weights: array{c1: float, c2: float, c3: float}, warnings: list<string>}
     */
    public function calculate(array $snapshot, array $ueqRows): array
    {
        $technical = is_array($snapshot['technical_informants'] ?? null) ? $snapshot['technical_informants'] : [];
        if ($technical === [] || array_filter($technical, fn ($row): bool => ! is_array($row) || ($row['weights'] ?? null) === null) !== []) {
            return ['rows' => [], 'alternatives' => [], 'weights' => ['c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0], 'warnings' => ['SAW belum dihitung: data informan atau bobot belum lengkap.']];
        }
        $weights = ['c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0];
        foreach ($technical as $informant) {
            foreach ($informant['weights'] as $key => $value) {
                $weights[$key] += $value / 100;
            }
        }
        $weights = ['c1' => $weights['c1'] / count($technical), 'c2' => $weights['c2'] / count($technical), 'c3' => $weights['c3'] / count($technical)];
        $unitIds = array_values(array_unique(array_map(fn (array $row): int => (int) $row['evaluation_unit_id'], $ueqRows)));
        $units = EvaluationUnit::query()->whereIn('id', $unitIds)->get()->keyBy('id');
        $alternatives = [];
        foreach ($unitIds as $unitId) {
            $rows = array_values(array_filter($ueqRows, fn (array $row): bool => (int) $row['evaluation_unit_id'] === $unitId));
            if (array_filter($rows, fn (array $row): bool => $row['gap'] === null) !== []) {
                continue;
            }
            $assessments = [];
            foreach ($technical as $informant) {
                foreach ($informant['assessments'] as $assessment) {
                    if ((int) $assessment['evaluation_unit_id'] === $unitId) {
                        $assessments[] = $assessment;
                    }
                }
            }
            if ($assessments === []) {
                continue;
            }
            $alternatives[] = new SawAlternative($units[$unitId]->code, $unitId, array_sum(array_column($rows, 'gap')) / count($rows), array_sum(array_column($assessments, 'estimated_days')) / count($assessments), array_sum(array_column($assessments, 'architecture_urgency')) / count($assessments));
        }
        if (count($alternatives) < 2) {
            return ['rows' => [], 'alternatives' => [], 'weights' => $weights, 'warnings' => ['SAW belum dihitung: minimal dua alternatif lengkap diperlukan.']];
        }
        $rows = array_map(fn ($row) => ['evaluation_unit_id' => $row->alternative->unitId, 'x1_gap' => $row->alternative->gap, 'x2_days' => $row->alternative->meanDays, 'x3_urgency' => $row->alternative->meanUrgency, 'r1' => $row->r1, 'r2' => $row->r2, 'r3' => $row->r3, 'contribution_c1' => $row->contributionC1, 'contribution_c2' => $row->contributionC2, 'contribution_c3' => $row->contributionC3, 'preference_value' => $row->preferenceValue, 'rank' => $row->rank, 'is_tied' => $row->isTied], $this->calculator->rank($alternatives, $weights));

        return [
            'rows' => $rows,
            'alternatives' => $alternatives,
            'weights' => $weights,
            'warnings' => max(array_map(fn ($r) => $r->gap, $alternatives)) === 0.0 ? ['Semua gap UEQ bernilai nol; normalisasi C1 ditetapkan nol.'] : [],
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
