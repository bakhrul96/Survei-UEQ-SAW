<?php

namespace App\Application\Calculation;

use App\Domain\Sensitivity\SensitivityCalculator;
use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CalculationRunService
{
    public const ALGORITHM_VERSION = 'ueq-preview-v1';

    public function __construct(
        private readonly CalculationInputSnapshot $snapshots,
        private readonly UeqResultWriter $resultWriter,
        private readonly SawResultWriter $sawWriter,
        private readonly SensitivityCalculator $sensitivityCalculator = new SensitivityCalculator,
        private readonly SensitivityResultWriter $sensitivityWriter = new SensitivityResultWriter,
    ) {}

    public function preview(EvaluationPeriod $period, User $actor): CalculationRun
    {
        return DB::transaction(function () use ($period, $actor): CalculationRun {
            $lockedPeriod = EvaluationPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $snapshotRevision = $lockedPeriod->calculation_input_revision;
            $snapshot = $this->snapshots->for($lockedPeriod, self::ALGORITHM_VERSION);
            $inputHash = $this->snapshots->hash($snapshot);
            $calculation = $this->resultWriter->calculate($snapshot);
            $sawCalculation = $this->sawWriter->calculate($snapshot, $calculation['rows']);
            $currentRevision = EvaluationPeriod::query()
                ->lockForUpdate()
                ->findOrFail($lockedPeriod->id)
                ->calculation_input_revision;
            $status = $currentRevision === $snapshotRevision ? 'preview' : 'stale';

            CalculationRun::query()
                ->where('evaluation_period_id', $lockedPeriod->id)
                ->where('status', 'preview')
                ->where('input_hash', '!=', $inputHash)
                ->update(['status' => 'stale']);

            $run = CalculationRun::query()->create([
                'evaluation_period_id' => $lockedPeriod->id,
                'algorithm_version' => self::ALGORITHM_VERSION,
                'status' => $status,
                'input_hash' => $inputHash,
                'input_snapshot' => $snapshot,
                'warnings' => array_values(array_unique([...$calculation['warnings'], ...$sawCalculation['warnings']])),
                'included_count' => count($snapshot['included_submission_ids']),
                'excluded_count' => count($snapshot['excluded_submission_ids']),
                'created_by' => $actor->id,
                'calculated_at' => now(),
            ]);

            $this->resultWriter->write($run, $calculation['rows'], $calculation['pooledRows']);
            $this->sawWriter->write($run, $sawCalculation['rows']);

            if ($sawCalculation['alternatives'] !== []) {
                /** @var array{S1: array{c1: string, c2: string, c3: string}, S2: array{c1: string, c2: string, c3: string}} $configuredScenarios */
                $configuredScenarios = $snapshot['configuration']['sensitivity_scenarios'];
                $sensitivityScenarios = $this->sensitivityCalculator->calculate(
                    $sawCalculation['alternatives'],
                    $sawCalculation['weights'],
                    $configuredScenarios,
                );
                $this->sensitivityWriter->write($run, $sensitivityScenarios);
            }

            AuditEvent::query()->create([
                'action' => 'calculation_run.created',
                'auditable_type' => CalculationRun::class,
                'auditable_id' => $run->id,
                'actor_id' => $actor->id,
                'old_values' => null,
                'new_values' => [
                    'algorithm_version' => $run->algorithm_version,
                    'input_hash' => $run->input_hash,
                    'status' => $run->status,
                    'included_count' => $run->included_count,
                    'excluded_count' => $run->excluded_count,
                    'calculated_at' => $run->calculated_at->toIso8601String(),
                ],
            ]);

            return $run->load(['ueqResults', 'ueqPooledResults', 'sawResults', 'sensitivityResults']);
        });
    }

    public function lockAsOfficial(CalculationRun $run, User $actor): CalculationRun
    {
        return DB::transaction(function () use ($run, $actor): CalculationRun {
            $lockedRun = CalculationRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($lockedRun->status === 'stale') {
                throw new DomainException('Kalkulasi berstatus stale tidak dapat dikunci sebagai hasil resmi.');
            }

            CalculationRun::query()
                ->where('evaluation_period_id', $lockedRun->evaluation_period_id)
                ->where('status', 'official')
                ->where('id', '!=', $lockedRun->id)
                ->update(['status' => 'archived']);

            $lockedRun->update([
                'status' => 'official',
                'locked_by' => $actor->id,
                'official_locked_at' => now(),
            ]);

            return $lockedRun->fresh();
        });
    }
}
