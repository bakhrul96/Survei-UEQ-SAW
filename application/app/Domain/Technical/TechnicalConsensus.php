<?php

namespace App\Domain\Technical;

use App\Models\CriteriaWeight;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\TechnicalAssessment;
use Illuminate\Support\Collection;

class TechnicalConsensus
{
    public function for(EvaluationPeriod $period): object
    {
        $assessments = TechnicalAssessment::query()
            ->whereHas('informant', fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->get()
            ->groupBy('evaluation_unit_id');

        $byUnit = EvaluationUnit::query()
            ->forWongReang()
            ->orderBy('display_order')
            ->get()
            ->mapWithKeys(function (EvaluationUnit $unit) use ($assessments): array {
                $rows = $assessments->get($unit->id, collect());

                return [$unit->id => [
                    'mean_days' => $rows->isEmpty() ? null : (float) $rows->avg('estimated_days'),
                    'mean_urgency' => $rows->isEmpty() ? null : (float) $rows->avg('architecture_urgency'),
                ]];
            })
            ->all();

        $weights = CriteriaWeight::query()
            ->whereHas('informant', fn ($query) => $query->where('evaluation_period_id', $period->id))
            ->get();

        $normalizedWeights = $weights->isEmpty()
            ? ['c1' => null, 'c2' => null, 'c3' => null]
            : $this->normalizedWeights($weights);

        return (object) ['assessments' => $byUnit, 'weights' => $normalizedWeights];
    }

    /**
     * @param  Collection<int, CriteriaWeight>  $weights
     * @return array{c1: float, c2: float, c3: float}
     */
    private function normalizedWeights(Collection $weights): array
    {
        $averages = [
            'c1' => (float) $weights->avg('c1_points'),
            'c2' => (float) $weights->avg('c2_points'),
            'c3' => (float) $weights->avg('c3_points'),
        ];
        $total = array_sum($averages);

        return [
            'c1' => $averages['c1'] / $total,
            'c2' => $averages['c2'] / $total,
            'c3' => $averages['c3'] / $total,
        ];
    }
}
