<?php

namespace App\Application\Reporting;

use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use App\Models\SawResult;
use App\Models\UeqResult;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use LogicException;

final class AggregateReportQuery
{
    public function __construct(
        private readonly SensitivityComparisonQuery $sensitivityComparison = new SensitivityComparisonQuery,
    ) {}

    public function for(EvaluationPeriod $period): AggregateReportData
    {
        $relations = [
            'ueqResults.unit',
            'sawResults.unit',
            'sensitivityResults.evaluationUnit',
            'expertJudgments.evaluationUnit',
            'expertJudgments.reviewer',
            'lockedBy',
        ];
        $selectedRun = $period->officialRun()->with($relations)->first()
            ?? CalculationRun::query()
                ->where('evaluation_period_id', $period->id)
                ->where('status', 'preview')
                ->with($relations)
                ->latest('id')
                ->first();

        $emptyComparison = new SensitivityComparisonData(
            collect(),
            ['S1' => false, 'S2' => false],
            ['S1' => [], 'S2' => []],
        );
        if ($selectedRun === null) {
            return new AggregateReportData(
                $period,
                null,
                false,
                collect(),
                collect(),
                collect(),
                collect(),
                collect(),
                $emptyComparison,
            );
        }

        $snapshot = $selectedRun->getAttribute('input_snapshot');
        $benchmarkRows = is_array($snapshot) && is_array($snapshot['benchmarks'] ?? null)
            ? array_values(array_filter($snapshot['benchmarks'], is_array(...)))
            : [];
        $benchmarks = collect($benchmarkRows)->sortBy('scale')->values();

        $ueqSummary = $selectedRun->ueqResults->groupBy('evaluation_unit_id')->map(function (EloquentCollection $results): array {
            $first = $results->first();
            if (! $first instanceof UeqResult || ! $first->unit instanceof EvaluationUnit) {
                throw new LogicException('Hasil UEQ tersimpan tanpa relasi unit yang valid.');
            }

            return [
                'unit_id' => $first->unit->id,
                'unit_code' => $first->unit->code,
                'unit_name' => $first->unit->name,
                'scales' => $results->mapWithKeys(fn (UeqResult $result): array => [
                    $result->scale => [
                        'n' => $result->n,
                        'mean' => $result->mean,
                        'standard_deviation' => $result->standard_deviation,
                        'standard_error' => $result->standard_error,
                        'ci95_lower' => $result->ci95_lower,
                        'ci95_upper' => $result->ci95_upper,
                        'cronbach_alpha' => $result->cronbach_alpha,
                        'gap' => $result->gap,
                    ],
                ])->sortKeys()->all(),
                'overall_gap' => $results->whereNotNull('gap')->avg('gap'),
            ];
        })->sortBy('unit_code')->values();

        $sawRanking = $selectedRun->sawResults->sortBy([
            ['rank', 'asc'],
            ['evaluation_unit_id', 'asc'],
        ])->map(function (SawResult $result): array {
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

        $comparison = $this->sensitivityComparison->forRun($selectedRun);
        $operationalBacklog = $selectedRun->expertJudgments
            ->sortBy('operational_order')
            ->map(function (ExpertJudgment $judgment): array {
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

        return new AggregateReportData(
            period: $period,
            selectedRun: $selectedRun,
            isOfficial: $selectedRun->status === 'official',
            benchmarks: $benchmarks,
            ueqSummary: $ueqSummary,
            sawRanking: $sawRanking,
            sensitivityMatrix: $comparison->rows,
            operationalBacklog: $operationalBacklog,
            sensitivityComparison: $comparison,
        );
    }
}
