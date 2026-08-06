<?php

namespace App\Application\Reporting;

use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;

class AggregateReportQuery
{
    public function for(EvaluationPeriod $period): AggregateReportData
    {
        $officialRun = CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->where('status', 'official')
            ->with(['ueqResults.unit', 'sawResults.unit', 'sensitivityResults.evaluationUnit', 'expertJudgments.evaluationUnit', 'lockedBy'])
            ->latest('id')
            ->first();

        $latestRun = $officialRun ?? CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->with(['ueqResults.unit', 'sawResults.unit', 'sensitivityResults.evaluationUnit', 'expertJudgments.evaluationUnit', 'lockedBy'])
            ->latest('id')
            ->first();

        $targetRun = $officialRun ?? $latestRun;

        $ueqSummary = collect();
        $sawRanking = collect();
        $sensitivityMatrix = collect();
        $operationalBacklog = collect();

        if ($targetRun) {
            $ueqSummary = $targetRun->ueqResults->groupBy('evaluation_unit_id')->map(function ($results) {
                $unit = $results->first()->unit;

                return [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'unit_name' => $unit->name,
                    'scales' => $results->mapWithKeys(fn ($r) => [
                        $r->scale => [
                            'n' => $r->n,
                            'mean' => $r->mean,
                            'sd' => $r->standard_deviation,
                            'alpha' => $r->cronbach_alpha,
                            'gap' => $r->gap,
                        ],
                    ])->all(),
                    'overall_gap' => $results->whereNotNull('gap')->avg('gap'),
                ];
            })->values();

            $sawRanking = $targetRun->sawResults->sortBy('rank')->map(fn ($r) => [
                'unit_id' => $r->evaluation_unit_id,
                'unit_code' => $r->unit->code,
                'unit_name' => $r->unit->name,
                'x1_gap' => $r->x1_gap,
                'x2_days' => $r->x2_days,
                'x3_urgency' => $r->x3_urgency,
                'r1' => $r->r1,
                'r2' => $r->r2,
                'r3' => $r->r3,
                'contribution_c1' => $r->contribution_c1,
                'contribution_c2' => $r->contribution_c2,
                'contribution_c3' => $r->contribution_c3,
                'vi' => $r->preference_value,
                'rank' => $r->rank,
                'is_tied' => $r->is_tied,
            ])->values();

            $sensByUnit = $targetRun->sensitivityResults->groupBy('evaluation_unit_id');
            $sensitivityMatrix = $sensByUnit->map(function ($results) {
                $unit = $results->first()->evaluationUnit;

                return [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'unit_name' => $unit->name,
                    'scenarios' => $results->mapWithKeys(fn ($r) => [
                        $r->scenario->value => [
                            'preference_value' => $r->preference_value,
                            'rank' => $r->rank,
                            'delta_rank' => $r->delta_rank,
                        ],
                    ])->all(),
                ];
            })->values();

            $operationalBacklog = $targetRun->expertJudgments->sortBy('operational_order')->map(fn ($ej) => [
                'operational_order' => $ej->operational_order,
                'unit_id' => $ej->evaluation_unit_id,
                'unit_code' => $ej->evaluationUnit->code,
                'unit_name' => $ej->evaluationUnit->name,
                'decision' => $ej->decision,
                'reason' => $ej->reason,
                'reviewer_name' => $ej->reviewer->name ?? 'Admin',
                'updated_at' => $ej->updated_at?->toIso8601String(),
            ])->values();
        }

        return new AggregateReportData(
            period: $period,
            officialRun: $officialRun,
            latestRun: $latestRun,
            ueqSummary: $ueqSummary,
            sawRanking: $sawRanking,
            sensitivityMatrix: $sensitivityMatrix,
            operationalBacklog: $operationalBacklog,
        );
    }
}
