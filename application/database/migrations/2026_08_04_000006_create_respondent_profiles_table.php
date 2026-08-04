<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respondent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('anonymous_respondent_id')->constrained()->cascadeOnDelete();
            $table->timestamp('consented_at');
            $table->unsignedTinyInteger('age');
            $table->boolean('is_indramayu_resident');
            $table->boolean('has_used_wong_reang');
            $table->boolean('eligible');
            $table->timestamp('screened_at');
            $table->timestamps();
            $table->unique(['evaluation_period_id', 'anonymous_respondent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respondent_profiles');
    }
};
