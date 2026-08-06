<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_readiness_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('reference', 2048);
            $table->text('notes');
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->unique(['evaluation_period_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_readiness_evidence');
    }
};
