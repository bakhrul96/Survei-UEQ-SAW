<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->decimal('sensitivity_s1_c1', 8, 6)->default(0.600000);
            $table->decimal('sensitivity_s1_c2', 8, 6)->default(0.200000);
            $table->decimal('sensitivity_s1_c3', 8, 6)->default(0.200000);
            $table->decimal('sensitivity_s2_c1', 8, 6)->default(0.200000);
            $table->decimal('sensitivity_s2_c2', 8, 6)->default(0.400000);
            $table->decimal('sensitivity_s2_c3', 8, 6)->default(0.400000);
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'sensitivity_s1_c1',
                'sensitivity_s1_c2',
                'sensitivity_s1_c3',
                'sensitivity_s2_c1',
                'sensitivity_s2_c2',
                'sensitivity_s2_c3',
            ]);
        });
    }
};
