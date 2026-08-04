<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('draft');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedTinyInteger('minimum_age')->default(17);
            $table->unsignedInteger('minimum_per_unit')->default(20);
            $table->unsignedInteger('target_per_unit')->default(30);
            $table->text('target_basis');
            $table->longText('consent_text');
            $table->unsignedInteger('fast_response_seconds')->default(120);
            $table->string('instrument_version')->default('UEQ-ID-26-v1');
            $table->string('instrument_source')->nullable();
            $table->timestamp('instrument_verified_at')->nullable();
            $table->timestamp('configuration_locked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
