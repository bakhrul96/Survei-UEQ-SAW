<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('anonymous_respondent_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('survey_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->string('instrument_version');
            $table->string('status')->default('submitted');
            $table->timestamp('started_at');
            $table->timestamp('completed_at');
            $table->unsignedInteger('duration_seconds');
            $table->unsignedTinyInteger('session_sequence');
            $table->timestamps();

            $table->unique(
                ['evaluation_period_id', 'anonymous_respondent_id', 'evaluation_unit_id'],
                'one_submission_per_period_respondent_unit',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_submissions');
    }
};
