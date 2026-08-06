<?php

namespace App\Application\Calculation;

use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class RecordMinimumSampleDeviation
{
    public function handle(
        CalculationRun $run,
        string $reason,
        string $approvalReference,
        User $actor,
    ): CalculationRun {
        return DB::transaction(function () use ($run, $reason, $approvalReference, $actor): CalculationRun {
            $lockedRun = CalculationRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($lockedRun->status !== 'preview') {
                throw new DomainException('Keputusan penyimpangan hanya dapat dicatat pada run preview.');
            }

            $reason = trim($reason);
            $approvalReference = trim($approvalReference);
            if ($reason === '' || $approvalReference === '') {
                throw new DomainException('Alasan dan referensi persetujuan pembimbing wajib diisi.');
            }

            $oldValues = [
                'reason' => $lockedRun->minimum_deviation_reason,
                'approval_reference' => $lockedRun->minimum_deviation_approval_reference,
                'approved_by' => $lockedRun->minimum_deviation_approved_by,
                'approved_at' => $lockedRun->minimum_deviation_approved_at?->toIso8601String(),
            ];

            $lockedRun->update([
                'minimum_deviation_reason' => $reason,
                'minimum_deviation_approval_reference' => $approvalReference,
                'minimum_deviation_approved_by' => $actor->id,
                'minimum_deviation_approved_at' => now(),
            ]);

            AuditEvent::query()->create([
                'action' => 'calculation_run.minimum_deviation_recorded',
                'auditable_type' => CalculationRun::class,
                'auditable_id' => $lockedRun->id,
                'actor_id' => $actor->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'reason' => $reason,
                    'approval_reference' => $approvalReference,
                    'approved_by' => $actor->id,
                    'approved_at' => $lockedRun->minimum_deviation_approved_at?->toIso8601String(),
                ],
            ]);

            return $lockedRun->fresh();
        });
    }
}
