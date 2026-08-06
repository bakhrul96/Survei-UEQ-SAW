<?php

namespace App\Application\Calculation;

use App\Domain\Quality\QualityDecision;
use App\Domain\Study\PeriodStatus;
use App\Models\CalculationRun;
use DomainException;

final class OfficialRunEligibility
{
    /** @return list<string> */
    public function issues(CalculationRun $run): array
    {
        $run->loadMissing(['period', 'sawResults', 'sensitivityResults', 'expertJudgments']);
        $issues = [];
        $snapshot = $run->getAttribute('input_snapshot');

        if ($run->status !== 'preview') {
            $issues[] = 'Hanya calculation run berstatus preview yang dapat dikunci.';
        }
        if ($run->period->status !== PeriodStatus::Closed) {
            $issues[] = 'Periode harus berstatus closed sebelum hasil resmi dikunci.';
        }
        if (! is_array($snapshot)) {
            return [...$issues, 'Input snapshot calculation run tidak valid.'];
        }

        $configuration = is_array($snapshot['configuration'] ?? null) ? $snapshot['configuration'] : [];
        $snapshotRevision = $configuration['calculation_input_revision'] ?? null;
        if (! is_numeric($snapshotRevision)
            || (int) $snapshotRevision !== $run->period->calculation_input_revision) {
            $issues[] = 'Revision input snapshot tidak sama dengan revision periode saat ini.';
        }

        $qualityDecisions = $this->arrayRows($snapshot['quality_decisions'] ?? null);
        if (collect($qualityDecisions)->contains(
            fn (array $decision): bool => ! in_array($decision['decision'] ?? null, [
                QualityDecision::Included->value,
                QualityDecision::Excluded->value,
            ], true),
        )) {
            $issues[] = 'Semua submission harus memiliki keputusan kualitas included atau excluded.';
        }

        $rawAnswers = is_array($snapshot['included_raw_answers'] ?? null)
            ? $snapshot['included_raw_answers']
            : [];
        if ($this->hasIncompleteIncludedAnswers($qualityDecisions, $rawAnswers)) {
            $issues[] = 'Setiap submission included harus memiliki jawaban item tepat 1 sampai 26.';
        }

        $this->appendMinimumSampleIssues($issues, $run, $snapshot, $qualityDecisions);

        $technicalConsensus = is_array($snapshot['technical_consensus'] ?? null)
            ? $snapshot['technical_consensus']
            : [];
        $informantCount = $technicalConsensus['informant_count'] ?? null;
        if (($technicalConsensus['is_complete'] ?? false) !== true
            || ! is_numeric($informantCount)
            || (int) $informantCount < 3
            || (int) $informantCount > 5) {
            $issues[] = 'Konsensus teknis lengkap dari 3 sampai 5 informan wajib tersedia.';
        }

        if (trim((string) $run->algorithm_version) === '') {
            $issues[] = 'Versi algoritma wajib tercatat pada calculation run.';
        }
        $benchmarks = $this->arrayRows($snapshot['benchmarks'] ?? null);
        if ($benchmarks === [] || collect($benchmarks)->contains(
            fn (array $benchmark): bool => trim((string) ($benchmark['source'] ?? '')) === ''
                || trim((string) ($benchmark['version'] ?? '')) === '',
        )) {
            $issues[] = 'Setiap benchmark harus memiliki source dan version.';
        }

        if ($run->sawResults->count() < 2) {
            $issues[] = 'Minimal dua alternatif SAW lengkap diperlukan.';
        }
        if (! $this->hasCompleteSensitivityResults($run)) {
            $issues[] = 'Hasil sensitivitas S0, S1, dan S2 harus lengkap untuk setiap alternatif.';
        }
        if (! $this->hasCompleteOperationalBacklog($run)) {
            $issues[] = 'Backlog operasional harus lengkap dan berurutan sebelum hasil resmi dikunci.';
        }

        return array_values(array_unique($issues));
    }

    public function assertEligible(CalculationRun $run): void
    {
        $issues = $this->issues($run);

        if ($issues !== []) {
            throw new DomainException(implode("\n", $issues));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $qualityDecisions
     * @param  array<mixed>  $rawAnswers
     */
    private function hasIncompleteIncludedAnswers(array $qualityDecisions, array $rawAnswers): bool
    {
        foreach ($qualityDecisions as $decision) {
            if (($decision['decision'] ?? null) !== QualityDecision::Included->value) {
                continue;
            }

            $submissionId = $decision['submission_id'] ?? null;
            $answers = $rawAnswers[(string) $submissionId] ?? null;
            if (! is_array($answers)) {
                return true;
            }

            $itemOrders = array_map('intval', array_keys($answers));
            sort($itemOrders);
            if ($itemOrders !== range(1, 26)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $issues
     * @param  array<string, mixed>  $snapshot
     * @param  list<array<string, mixed>>  $qualityDecisions
     */
    private function appendMinimumSampleIssues(
        array &$issues,
        CalculationRun $run,
        array $snapshot,
        array $qualityDecisions,
    ): void {
        if ($this->hasApprovedMinimumDeviation($run)) {
            return;
        }

        $minimum = $snapshot['configuration']['minimum_per_unit'] ?? null;
        if (! is_numeric($minimum)) {
            $issues[] = 'Minimum respons included per unit tidak tersedia pada snapshot.';

            return;
        }

        $includedByUnit = [];
        foreach ($qualityDecisions as $decision) {
            if (($decision['decision'] ?? null) !== QualityDecision::Included->value
                || ! is_numeric($decision['evaluation_unit_id'] ?? null)) {
                continue;
            }
            $unitId = (int) $decision['evaluation_unit_id'];
            $includedByUnit[$unitId] = ($includedByUnit[$unitId] ?? 0) + 1;
        }

        foreach ($this->arrayRows($snapshot['units'] ?? null) as $unit) {
            if (($unit['is_active'] ?? false) !== true || ! is_numeric($unit['id'] ?? null)) {
                continue;
            }
            $unitId = (int) $unit['id'];
            $actual = $includedByUnit[$unitId] ?? 0;
            if ($actual < (int) $minimum) {
                $code = trim((string) ($unit['code'] ?? $unitId));
                $issues[] = "{$code} baru memiliki {$actual} dari minimum {$minimum} respons included.";
            }
        }
    }

    private function hasApprovedMinimumDeviation(CalculationRun $run): bool
    {
        return trim((string) $run->minimum_deviation_reason) !== ''
            && trim((string) $run->minimum_deviation_approval_reference) !== ''
            && $run->minimum_deviation_approved_by !== null
            && $run->minimum_deviation_approved_at !== null;
    }

    private function hasCompleteSensitivityResults(CalculationRun $run): bool
    {
        if ($run->sawResults->count() < 2
            || $run->sensitivityResults->count() !== $run->sawResults->count() * 3) {
            return false;
        }

        foreach ($run->sawResults as $sawResult) {
            $scenarios = $run->sensitivityResults
                ->where('evaluation_unit_id', $sawResult->evaluation_unit_id)
                ->map(fn ($result): string => $result->scenario->value)
                ->sort()
                ->values()
                ->all();

            if ($scenarios !== ['S0', 'S1', 'S2']) {
                return false;
            }
        }

        return true;
    }

    private function hasCompleteOperationalBacklog(CalculationRun $run): bool
    {
        $sawUnitIds = $run->sawResults->pluck('evaluation_unit_id')->sort()->values()->all();
        $backlogUnitIds = $run->expertJudgments->pluck('evaluation_unit_id')->sort()->values()->all();
        $orders = $run->expertJudgments->pluck('operational_order')->sort()->values()->all();

        return $sawUnitIds !== []
            && $sawUnitIds === $backlogUnitIds
            && $orders === range(1, count($sawUnitIds));
    }

    /** @return list<array<string, mixed>> */
    private function arrayRows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
    }
}
