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
    }

    public function down(): void
    {
        Schema::dropIfExists('ueq_results');
        Schema::dropIfExists('calculation_runs');
    }
};
