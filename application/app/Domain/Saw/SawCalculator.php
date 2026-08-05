<?php

namespace App\Domain\Saw;

use DomainException;

class SawCalculator
{
    private const TOLERANCE = 0.000001;

    /**
     * @param  list<SawAlternative>  $alternatives
     * @param  array{c1: float, c2: float, c3: float}  $weights
     * @return list<SawResultData>
     */
    public function rank(array $alternatives, array $weights): array
    {
        if (count($alternatives) < 2) {
            throw new DomainException('Minimal dua alternatif lengkap diperlukan.');
        }

        foreach ($alternatives as $alternative) {
            if ($alternative->meanDays <= 0) {
                throw new DomainException('estimated_days harus lebih dari nol.');
            }
            if ($alternative->meanUrgency < 0 || $alternative->gap < 0) {
                throw new DomainException('Nilai SAW tidak boleh negatif.');
            }
        }

        if (abs(array_sum($weights) - 1.0) > self::TOLERANCE) {
            throw new DomainException('Bobot SAW harus berjumlah satu.');
        }

        $maxGap = max(array_map(fn (SawAlternative $row): float => $row->gap, $alternatives));
        $minDays = min(array_map(fn (SawAlternative $row): float => $row->meanDays, $alternatives));
        $maxUrgency = max(array_map(fn (SawAlternative $row): float => $row->meanUrgency, $alternatives));

        $rows = array_map(function (SawAlternative $alternative) use ($maxGap, $minDays, $maxUrgency, $weights): SawResultData {
            $r1 = $maxGap === 0.0 ? 0.0 : $alternative->gap / $maxGap;
            $r2 = $minDays / $alternative->meanDays;
            $r3 = $maxUrgency === 0.0 ? 0.0 : $alternative->meanUrgency / $maxUrgency;
            $c1 = $weights['c1'] * $r1;
            $c2 = $weights['c2'] * $r2;
            $c3 = $weights['c3'] * $r3;

            return new SawResultData($alternative, $r1, $r2, $r3, $c1, $c2, $c3, $c1 + $c2 + $c3, 0, false);
        }, $alternatives);

        usort($rows, fn (SawResultData $left, SawResultData $right): int => ($right->preferenceValue <=> $left->preferenceValue) ?: ($left->alternative->unitCode <=> $right->alternative->unitCode));

        $ranked = [];
        foreach ($rows as $index => $row) {
            $previous = $ranked[$index - 1] ?? null;
            $rank = $previous !== null && abs($previous->preferenceValue - $row->preferenceValue) <= self::TOLERANCE ? $previous->rank : $index + 1;
            $isTied = count(array_filter($rows, fn (SawResultData $candidate): bool => abs($candidate->preferenceValue - $row->preferenceValue) <= self::TOLERANCE)) > 1;
            $ranked[] = new SawResultData($row->alternative, $row->r1, $row->r2, $row->r3, $row->contributionC1, $row->contributionC2, $row->contributionC3, $row->preferenceValue, $rank, $isTied);
        }

        return $ranked;
    }
}
