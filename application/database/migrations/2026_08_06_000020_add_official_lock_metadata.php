<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->foreignId('official_calculation_run_id')
                ->nullable()
                ->constrained('calculation_runs')
                ->restrictOnDelete();
        });

        Schema::table('calculation_runs', function (Blueprint $table): void {
            $table->text('minimum_deviation_reason')->nullable();
            $table->string('minimum_deviation_approval_reference')->nullable();
            $table->foreignId('minimum_deviation_approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('minimum_deviation_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('official_calculation_run_id');
        });

        Schema::table('calculation_runs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('minimum_deviation_approved_by');
            $table->dropColumn([
                'minimum_deviation_reason',
                'minimum_deviation_approval_reference',
                'minimum_deviation_approved_at',
            ]);
        });
    }
};
