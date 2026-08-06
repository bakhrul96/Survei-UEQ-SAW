<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $runIds = DB::table('calculation_runs')->orderBy('id')->pluck('id');

        foreach ($runIds as $runId) {
            $run = DB::table('calculation_runs')->where('id', $runId)->first();
            if ($run === null) {
                continue;
            }

            $sawRows = DB::table('saw_results')
                ->join('evaluation_units', 'evaluation_units.id', '=', 'saw_results.evaluation_unit_id')
                ->where('saw_results.calculation_run_id', $runId)
                ->orderBy('saw_results.rank')
                ->orderBy('evaluation_units.code')
                ->get(['saw_results.evaluation_unit_id']);
            $unitIds = $sawRows->pluck('evaluation_unit_id')->map(fn (mixed $id): int => (int) $id)->all();

            if ($unitIds === []) {
                DB::table('expert_judgments')->where('calculation_run_id', $runId)->delete();

                continue;
            }

            DB::table('expert_judgments')
                ->where('calculation_run_id', $runId)
                ->whereNotIn('evaluation_unit_id', $unitIds)
                ->delete();

            foreach ($unitIds as $index => $unitId) {
                $existing = DB::table('expert_judgments')
                    ->where('calculation_run_id', $runId)
                    ->where('evaluation_unit_id', $unitId)
                    ->first();

                if ($existing === null) {
                    DB::table('expert_judgments')->insert([
                        'calculation_run_id' => $runId,
                        'evaluation_unit_id' => $unitId,
                        'operational_order' => $index + 1,
                        'decision' => 'unchanged',
                        'reason' => 'Mengikuti urutan analitis SAW S0.',
                        'reviewer_id' => $run->created_by,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('expert_judgments')->where('id', $existing->id)->update([
                        'operational_order' => $index + 1,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        Schema::table('expert_judgments', function (Blueprint $table): void {
            $table->unique(['calculation_run_id', 'operational_order'], 'exp_run_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('expert_judgments', function (Blueprint $table): void {
            $table->dropUnique('exp_run_order_unique');
        });
    }
};
