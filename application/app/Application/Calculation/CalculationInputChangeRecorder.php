<?php

namespace App\Application\Calculation;

use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\User;

final class CalculationInputChangeRecorder
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function record(
        EvaluationPeriod $period,
        User $actor,
        string $action,
        string $auditableType,
        int $auditableId,
        ?array $oldValues,
        array $newValues,
    ): void {
        $lockedPeriod = EvaluationPeriod::query()
            ->lockForUpdate()
            ->findOrFail($period->id);

        $lockedPeriod->increment('calculation_input_revision');

        CalculationRun::query()
            ->where('evaluation_period_id', $period->id)
            ->where('status', 'preview')
            ->update(['status' => 'stale']);

        AuditEvent::query()->create([
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'actor_id' => $actor->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
