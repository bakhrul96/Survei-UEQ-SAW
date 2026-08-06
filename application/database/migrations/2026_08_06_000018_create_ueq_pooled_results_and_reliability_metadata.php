<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ueq_results', function (Blueprint $table): void {
            $table->string('reliability_unavailable_reason')->nullable()->after('cronbach_alpha');
            $table->json('reliability_warnings')->after('reliability_unavailable_reason');
        });

        Schema::create('ueq_pooled_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('calculation_run_id')->constrained()->restrictOnDelete();
            $table->string('scope')->default('pooled');
            $table->string('scale');
            $table->unsignedInteger('n');
            $table->decimal('cronbach_alpha', 18, 10)->nullable();
            $table->string('unavailable_reason')->nullable();
            $table->json('warnings');
            $table->timestamps();

            $table->unique(['calculation_run_id', 'scope', 'scale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ueq_pooled_results');

        Schema::table('ueq_results', function (Blueprint $table): void {
            $table->dropColumn(['reliability_unavailable_reason', 'reliability_warnings']);
        });
    }
};
