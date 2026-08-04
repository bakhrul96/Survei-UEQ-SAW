<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ueq_benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->string('scale');
            $table->decimal('good_threshold', 8, 4);
            $table->string('source');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['version', 'scale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ueq_benchmarks');
    }
};
