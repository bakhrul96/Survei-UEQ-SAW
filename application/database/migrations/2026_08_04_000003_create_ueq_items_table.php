<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ueq_items', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->unsignedTinyInteger('order');
            $table->string('left_label');
            $table->string('right_label');
            $table->string('scale');
            $table->string('positive_pole');
            $table->timestamps();
            $table->unique(['version', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ueq_items');
    }
};
