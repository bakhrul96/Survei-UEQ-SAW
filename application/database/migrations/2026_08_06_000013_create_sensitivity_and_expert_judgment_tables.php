<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculation_runs', function (Blueprint $table) {
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('official_locked_at')->nullable();
        });

        Schema::create('sensitivity_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained()->cascadeOnDelete();
            $table->string('scenario');
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->decimal('preference_value', 18, 10);
            $table->unsignedInteger('rank');
            $table->integer('delta_rank');
            $table->boolean('is_tied')->default(false);
            $table->timestamps();

            $table->unique(['calculation_run_id', 'scenario', 'evaluation_unit_id'], 'sens_run_scenario_unit_unique');
        });

        Schema::create('expert_judgments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('operational_order');
            $table->string('decision')->default('adjusted');
            $table->text('reason');
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['calculation_run_id', 'evaluation_unit_id'], 'exp_run_unit_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_judgments');
        Schema::dropIfExists('sensitivity_results');

        Schema::table('calculation_runs', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropColumn(['locked_by', 'official_locked_at']);
        });
    }
};
