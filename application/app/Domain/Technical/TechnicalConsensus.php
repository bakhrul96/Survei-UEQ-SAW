<?php

namespace App\Domain\Technical;

use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalInformant;
use Illuminate\Support\Collection;

final class TechnicalConsensus
{
    public function for(EvaluationPeriod $period): TechnicalConsensusData
    {
        $units = EvaluationUnit::query()
            ->forWongReang()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $unitIds = $units->pluck('id')->map(fn (int $id): int => $id)->all();
        $informants = TechnicalInformant::query()
            ->where('evaluation_period_id', $period->id)
            ->with(['assessments', 'criteriaWeight'])
            ->orderBy('id')
            ->get();
        $informantCount = $informants->count();

        $unitConsensus = $units->mapWithKeys(function (EvaluationUnit $unit) use ($informants): array {
            $rows = $informants
                ->flatMap(fn (TechnicalInformant $informant) => $informant->assessments)
                ->where('evaluation_unit_id', $unit->id)
                ->values();
            $days = array_values($rows->pluck('estimated_days')->map(fn (mixed $value): float => (float) $value)->all());
            $urgencies = array_values($rows->pluck('architecture_urgency')->map(fn (mixed $value): float => (float) $value)->all());

            return [$unit->id => new TechnicalUnitConsensus(
                unitId: $unit->id,
                n: $rows->count(),
                meanDays: $this->mean($days),
                standardDeviationDays: $this->sampleStandardDeviation($days),
                meanUrgency: $this->mean($urgencies),
                standardDeviationUrgency: $this->sampleStandardDeviation($urgencies),
            )];
        })->all();

        $weights = $this->normalizedWeights($informants);
        $reasons = [];

        if ($informantCount < 3 || $informantCount > 5) {
            $reasons[] = 'Jumlah informan lengkap harus 3 sampai 5.';
        }

        $informantsComplete = $informants->every(function (TechnicalInformant $informant) use ($unitIds): bool {
            $assessmentUnitIds = $informant->assessments
                ->pluck('evaluation_unit_id')
                ->map(fn (int $id): int => $id)
                ->sort()
                ->values()
                ->all();
            $expectedUnitIds = $unitIds;
            sort($expectedUnitIds);

            return $assessmentUnitIds === $expectedUnitIds && $informant->criteriaWeight !== null;
        });

        if (! $informantsComplete) {
            $reasons[] = 'Setiap informan harus memiliki 13 penilaian dan satu alokasi bobot.';
        }

        if (collect($unitConsensus)->contains(fn (TechnicalUnitConsensus $unit): bool => $unit->n !== $informantCount)) {
            $reasons[] = 'Jumlah penilaian per modul tidak sama dengan jumlah informan.';
        }

        $weightTotal = array_sum(array_filter($weights, fn (?float $weight): bool => $weight !== null));
        if (in_array(null, $weights, true) || abs($weightTotal - 1.0) > 0.000001) {
            $reasons[] = 'Bobot konsensus belum lengkap atau tidak berjumlah satu.';
        }

        return new TechnicalConsensusData(
            informantCount: $informantCount,
            isComplete: $reasons === [],
            incompleteReasons: $reasons,
            units: $unitConsensus,
            weights: $weights,
        );
    }

    /** @param list<float> $values */
    private function mean(array $values): ?float
    {
        return $values === [] ? null : array_sum($values) / count($values);
    }

    /** @param list<float> $values */
    private function sampleStandardDeviation(array $values): ?float
    {
        $n = count($values);
        if ($n < 2) {
            return null;
        }

        $mean = array_sum($values) / $n;
        $sum = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $values));

        return sqrt($sum / ($n - 1));
    }

    /**
     * @param  Collection<int, TechnicalInformant>  $informants
     * @return array{c1: float|null, c2: float|null, c3: float|null}
     */
    private function normalizedWeights(Collection $informants): array
    {
        if ($informants->isEmpty() || $informants->contains(fn (TechnicalInformant $informant): bool => $informant->criteriaWeight === null)) {
            return ['c1' => null, 'c2' => null, 'c3' => null];
        }

        $averages = [
            'c1' => (float) $informants->avg(fn (TechnicalInformant $informant): int => $informant->criteriaWeight->c1_points),
            'c2' => (float) $informants->avg(fn (TechnicalInformant $informant): int => $informant->criteriaWeight->c2_points),
            'c3' => (float) $informants->avg(fn (TechnicalInformant $informant): int => $informant->criteriaWeight->c3_points),
        ];
        $total = array_sum($averages);

        if ($total <= 0.0) {
            return ['c1' => null, 'c2' => null, 'c3' => null];
        }

        return [
            'c1' => $averages['c1'] / $total,
            'c2' => $averages['c2'] / $total,
            'c3' => $averages['c3'] / $total,
        ];
    }
}
