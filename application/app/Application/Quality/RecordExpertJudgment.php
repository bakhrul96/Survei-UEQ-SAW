<?php

namespace App\Application\Quality;

use App\Models\CalculationRun;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class RecordExpertJudgment
{
    public function handle(
        CalculationRun $run,
        EvaluationUnit $unit,
        int $operationalOrder,
        string $reason,
        User $reviewer,
        string $decision = 'adjusted',
    ): ExpertJudgment {
        if ($operationalOrder < 1 || $operationalOrder > 13) {
            throw new DomainException('Urutan backlog operasional harus antara 1 sampai 13.');
        }

        if (trim($reason) === '') {
            throw new DomainException('Alasan keputusan expert judgment wajib diisi.');
        }

        return DB::transaction(function () use ($run, $unit, $operationalOrder, $reason, $reviewer, $decision): ExpertJudgment {
            return ExpertJudgment::query()->updateOrCreate(
                [
                    'calculation_run_id' => $run->id,
                    'evaluation_unit_id' => $unit->id,
                ],
                [
                    'operational_order' => $operationalOrder,
                    'decision' => $decision,
                    'reason' => trim($reason),
                    'reviewer_id' => $reviewer->id,
                ]
            );
        });
    }
}
