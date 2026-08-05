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
    ) {}

    public function preview(EvaluationPeriod $period, User $actor): CalculationRun
    {
        return DB::transaction(function () use ($period, $actor): CalculationRun {
            $snapshot = $this->snapshots->for($period, self::ALGORITHM_VERSION);
            $inputHash = $this->snapshots->hash($snapshot);
            $calculation = $this->resultWriter->calculate($snapshot);

            CalculationRun::query()
                ->where('evaluation_period_id', $period->id)
                ->where('status', 'preview')
                ->where('input_hash', '!=', $inputHash)
                ->update(['status' => 'stale']);

            $run = CalculationRun::query()->create([
                'evaluation_period_id' => $period->id,
                'algorithm_version' => self::ALGORITHM_VERSION,
                'status' => 'preview',
                'input_hash' => $inputHash,
                'input_snapshot' => $snapshot,
                'warnings' => $calculation['warnings'],
                'included_count' => count($snapshot['included_submission_ids']),
                'excluded_count' => count($snapshot['excluded_submission_ids']),
                'created_by' => $actor->id,
                'calculated_at' => now(),
            ]);

            $this->resultWriter->write($run, $calculation['rows']);

            return $run->load('ueqResults');
        });
    }
}
