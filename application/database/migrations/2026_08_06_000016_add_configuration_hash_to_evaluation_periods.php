<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->char('configuration_hash', 64)->nullable()->after('configuration_locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->dropColumn('configuration_hash');
        });
    }
};
