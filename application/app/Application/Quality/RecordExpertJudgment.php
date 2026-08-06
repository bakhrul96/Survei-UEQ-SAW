<?php

namespace App\Application\Quality;

use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\EvaluationUnit;
use App\Models\ExpertJudgment;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RecordExpertJudgment
{
    public function handle(
        CalculationRun $run,
        EvaluationUnit $unit,
        int $operationalOrder,
        string $reason,
        User $reviewer,
    ): ExpertJudgment {
        return DB::transaction(function () use ($run, $unit, $operationalOrder, $reason, $reviewer): ExpertJudgment {
            $lockedRun = CalculationRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($lockedRun->status === 'official') {
                throw new DomainException('Backlog hasil resmi tidak dapat diubah.');
            }
            if ($lockedRun->status !== 'preview') {
                throw new DomainException('Backlog hanya dapat diubah pada calculation run preview.');
            }

            $rows = ExpertJudgment::query()
                ->where('calculation_run_id', $lockedRun->id)
                ->with('evaluationUnit')
                ->orderBy('operational_order')
                ->lockForUpdate()
                ->get();
            $count = $rows->count();

            $target = $rows->firstWhere('evaluation_unit_id', $unit->id);
            if (! $target instanceof ExpertJudgment
                || ! $lockedRun->sawResults()->where('evaluation_unit_id', $unit->id)->exists()) {
                throw new DomainException('Modul tidak tersedia pada hasil SAW run ini.');
            }
            if ($operationalOrder < 1 || $operationalOrder > $count) {
                throw new DomainException("Urutan backlog operasional harus antara 1 sampai {$count}.");
            }

            $oldOrder = $target->operational_order;
            $reason = trim($reason);
            if ($oldOrder !== $operationalOrder && $reason === '') {
                throw new DomainException('Alasan keputusan expert judgment wajib diisi.');
            }

            $ordered = $rows->reject(fn (ExpertJudgment $row): bool => $row->id === $target->id)->values();
            $ordered->splice($operationalOrder - 1, 0, [$target]);
            $oldOrderMap = $rows->pluck('operational_order', 'evaluation_unit_id')->all();

            foreach ($ordered as $index => $row) {
                $row->update(['operational_order' => $count + 1000 + $index]);
            }
            foreach ($ordered as $index => $row) {
                $attributes = ['operational_order' => $index + 1];
                if ($row->id === $target->id && $reason !== '') {
                    $attributes += [
                        'decision' => 'adjusted',
                        'reason' => $reason,
                        'reviewer_id' => $reviewer->id,
                    ];
                }
                $row->update($attributes);
            }

            $newOrderMap = $ordered->mapWithKeys(
                fn (ExpertJudgment $row, int $index): array => [$row->evaluation_unit_id => $index + 1],
            )->all();
            AuditEvent::query()->create([
                'action' => 'expert_judgment.backlog_reordered',
                'auditable_type' => CalculationRun::class,
                'auditable_id' => $lockedRun->id,
                'actor_id' => $reviewer->id,
                'old_values' => ['order_by_unit' => $oldOrderMap],
                'new_values' => [
                    'order_by_unit' => $newOrderMap,
                    'moved_unit_id' => $unit->id,
                    'reason' => $reason,
                ],
            ]);

            return $target->fresh();
        });
    }
}
