<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('survey_submission_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('flags');
            $table->string('decision');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at');
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('old_values')->nullable();
            $table->json('new_values');
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('quality_reviews');
    }
};
