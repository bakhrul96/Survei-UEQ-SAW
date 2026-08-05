<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_informants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->restrictOnDelete();
            $table->string('anonymous_code');
            $table->timestamps();

            $table->unique(['evaluation_period_id', 'anonymous_code']);
        });

        Schema::create('technical_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_informant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_unit_id')->constrained()->restrictOnDelete();
            $table->decimal('estimated_days', 12, 2);
            $table->unsignedTinyInteger('architecture_urgency');
            $table->timestamps();

            $table->unique(['technical_informant_id', 'evaluation_unit_id']);
        });

        Schema::create('criteria_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_informant_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('c1_points');
            $table->unsignedTinyInteger('c2_points');
            $table->unsignedTinyInteger('c3_points');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria_weights');
        Schema::dropIfExists('technical_assessments');
        Schema::dropIfExists('technical_informants');
    }
};
