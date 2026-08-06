<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_reviews', function (Blueprint $table): void {
            $table->string('decision')->nullable()->change();
            $table->unsignedBigInteger('reviewed_by')->nullable()->change();
            $table->timestamp('reviewed_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('quality_reviews')->whereNull('decision')->delete();

        Schema::table('quality_reviews', function (Blueprint $table): void {
            $table->string('decision')->nullable(false)->change();
            $table->unsignedBigInteger('reviewed_by')->nullable(false)->change();
            $table->timestamp('reviewed_at')->nullable(false)->change();
        });
    }
};
