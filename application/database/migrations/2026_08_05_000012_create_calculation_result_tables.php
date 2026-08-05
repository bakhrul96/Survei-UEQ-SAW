<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculation_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
            $table->string('algorithm_version');
            $table->string('status');
            $table->string('input_hash', 64);
            $table->json('input_snapshot');
            $table->json('warnings');
            $table->unsignedInteger('included_count');
            $table->unsignedInteger('excluded_count');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->index(['evaluation_period_id', 'status']);
        });

        Schema::create('ueq_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->string('scale');
            $table->unsignedInteger('n');
            $table->decimal('mean', 18, 10)->nullable();
            $table->decimal('standard_deviation', 18, 10)->nullable();
            $table->decimal('standard_error', 18, 10)->nullable();
            $table->decimal('ci95_lower', 18, 10)->nullable();
            $table->decimal('ci95_upper', 18, 10)->nullable();
            $table->decimal('cronbach_alpha', 18, 10)->nullable();
            $table->decimal('gap', 18, 10)->nullable();
            $table->string('unavailable_reason')->nullable();
            $table->timestamps();

            $table->unique(['calculation_run_id', 'evaluation_unit_id', 'scale']);
        });

        Schema::create('saw_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->decimal('x1_gap', 18, 10);
            $table->decimal('x2_days', 18, 10);
            $table->decimal('x3_urgency', 18, 10);
            $table->decimal('r1', 18, 10);
            $table->decimal('r2', 18, 10);
            $table->decimal('r3', 18, 10);
            $table->decimal('contribution_c1', 18, 10);
            $table->decimal('contribution_c2', 18, 10);
            $table->decimal('contribution_c3', 18, 10);
            $table->decimal('preference_value', 18, 10);
            $table->unsignedInteger('rank');
            $table->boolean('is_tied');
            $table->timestamps();
            $table->unique(['calculation_run_id', 'evaluation_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saw_results');
        Schema::dropIfExists('ueq_results');
        Schema::dropIfExists('calculation_runs');
    }
};
