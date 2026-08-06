<?php

namespace App\Application\Reporting;

use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use App\Models\SawResult;
use App\Models\SensitivityResult;
use App\Models\UeqResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use LogicException;

class AggregateReportQuery
{
    public function for(EvaluationPeriod $period): AggregateReportData
    {
        $officialRun = CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->where('status', 'official')
            ->with(['ueqResults.unit', 'sawResults.unit', 'sensitivityResults.evaluationUnit', 'expertJudgments.evaluationUnit', 'expertJudgments.reviewer', 'lockedBy'])
            ->latest('id')
            ->first();

        $latestRun = $officialRun ?? CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->with(['ueqResults.unit', 'sawResults.unit', 'sensitivityResults.evaluationUnit', 'expertJudgments.evaluationUnit', 'expertJudgments.reviewer', 'lockedBy'])
            ->latest('id')
            ->first();

        $targetRun = $officialRun ?? $latestRun;

        $ueqSummary = collect();
        $sawRanking = collect();
        $sensitivityMatrix = collect();
        $operationalBacklog = collect();

        if ($targetRun) {
            $ueqSummary = $targetRun->ueqResults->groupBy('evaluation_unit_id')->map(function (EloquentCollection $results): array {
                $firstResult = $results->first();
                if (! $firstResult instanceof UeqResult || ! $firstResult->unit instanceof EvaluationUnit) {
                    throw new LogicException('Hasil UEQ tersimpan tanpa relasi unit yang valid.');
                }
                $unit = $firstResult->unit;

                return [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'unit_name' => $unit->name,
                    'scales' => $results->mapWithKeys(fn (UeqResult $r): array => [
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

            $sawRanking = $targetRun->sawResults->sortBy('rank')->map(function (SawResult $result): array {
                if (! $result->unit instanceof EvaluationUnit) {
                    throw new LogicException('Hasil SAW tersimpan tanpa relasi unit yang valid.');
                }

                return [
                    'unit_id' => $result->evaluation_unit_id,
                    'unit_code' => $result->unit->code,
                    'unit_name' => $result->unit->name,
                    'x1_gap' => $result->x1_gap,
                    'x2_days' => $result->x2_days,
                    'x3_urgency' => $result->x3_urgency,
                    'r1' => $result->r1,
                    'r2' => $result->r2,
                    'r3' => $result->r3,
                    'contribution_c1' => $result->contribution_c1,
                    'contribution_c2' => $result->contribution_c2,
                    'contribution_c3' => $result->contribution_c3,
                    'vi' => $result->preference_value,
                    'rank' => $result->rank,
                    'is_tied' => $result->is_tied,
                ];
            })->values();

            $sensByUnit = $targetRun->sensitivityResults->groupBy('evaluation_unit_id');
            $sensitivityMatrix = $sensByUnit->map(function (EloquentCollection $results): array {
                $firstResult = $results->first();
                if (! $firstResult instanceof SensitivityResult || ! $firstResult->evaluationUnit instanceof EvaluationUnit) {
                    throw new LogicException('Hasil sensitivitas tersimpan tanpa relasi unit yang valid.');
                }
                $unit = $firstResult->evaluationUnit;

                return [
                    'unit_id' => $unit->id,
                    'unit_code' => $unit->code,
                    'unit_name' => $unit->name,
                    'scenarios' => $results->mapWithKeys(fn (SensitivityResult $r): array => [
                        $r->scenario->value => [
                            'preference_value' => $r->preference_value,
                            'rank' => $r->rank,
                            'delta_rank' => $r->delta_rank,
                        ],
                    ])->all(),
                ];
            })->values();

            $operationalBacklog = $targetRun->expertJudgments->sortBy('operational_order')->map(function (ExpertJudgment $judgment): array {
                if (! $judgment->evaluationUnit instanceof EvaluationUnit) {
                    throw new LogicException('Expert judgment tersimpan tanpa relasi unit yang valid.');
                }

                return [
                    'operational_order' => $judgment->operational_order,
                    'unit_id' => $judgment->evaluation_unit_id,
                    'unit_code' => $judgment->evaluationUnit->code,
                    'unit_name' => $judgment->evaluationUnit->name,
                    'decision' => $judgment->decision,
                    'reason' => $judgment->reason,
                    'reviewer_name' => $judgment->reviewer->name ?? 'Admin',
                    'updated_at' => $judgment->updated_at?->toIso8601String(),
                ];
            })->values();
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
