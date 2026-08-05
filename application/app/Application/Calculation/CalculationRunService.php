<?php

namespace App\Application\Calculation;

use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CalculationRunService
{
    public const ALGORITHM_VERSION = 'ueq-preview-v1';

    public function __construct(
        private readonly CalculationInputSnapshot $snapshots,
        private readonly UeqResultWriter $resultWriter,
        private readonly SawResultWriter $sawWriter,
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

            $this->resultWriter->write($run, $calculation['rows']);
            $this->sawWriter->write($run, $sawCalculation['rows']);

            return $run->load(['ueqResults', 'sawResults']);
        });
    }
}
