<?php

namespace App\Application\Quality;

use App\Models\CalculationRun;
use App\Models\SawResult;
use LogicException;

final class InitializeOperationalBacklog
{
    public function handle(CalculationRun $run): void
    {
        if ($run->expertJudgments()->exists()) {
            throw new LogicException('Operational backlog can only be initialized once.');
        }

        $run->sawResults()
            ->with('unit')
            ->get()
            ->sortBy(fn (SawResult $row): string => sprintf('%06d:%s', $row->rank, $row->unit->code))
            ->values()
            ->each(fn (SawResult $row, int $index) => $run->expertJudgments()->create([
                'evaluation_unit_id' => $row->evaluation_unit_id,
                'operational_order' => $index + 1,
                'decision' => 'unchanged',
                'reason' => 'Mengikuti urutan analitis SAW S0.',
                'reviewer_id' => $run->created_by,
            ]));
    }
}
