<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->text('consent_data_description')->nullable()->after('consent_text');
            $table->text('consent_cookie_description')->nullable()->after('consent_data_description');
            $table->unsignedSmallInteger('consent_estimated_minutes')->default(10)->after('consent_cookie_description');
            $table->text('consent_withdrawal_description')->nullable()->after('consent_estimated_minutes');
            $table->string('research_contact')->default('peneliti@example.test')->after('consent_withdrawal_description');
            $table->string('quality_rules_version')->default('quality-rules-v1')->after('fast_response_seconds');
            $table->boolean('identical_answers_flag_enabled')->default(true)->after('quality_rules_version');
        });

        DB::table('evaluation_periods')->whereNull('consent_data_description')->update([
            'consent_data_description' => 'Jawaban UEQ mentah, waktu pengisian, urutan modul, dan profil kelayakan tanpa nama disimpan untuk analisis penelitian.',
        ]);
        DB::table('evaluation_periods')->whereNull('consent_cookie_description')->update([
            'consent_cookie_description' => 'Cookie anonim digunakan untuk mencegah modul yang sama dinilai dua kali.',
        ]);
        DB::table('evaluation_periods')->whereNull('consent_withdrawal_description')->update([
            'consent_withdrawal_description' => 'Partisipasi sukarela dan dapat dihentikan sebelum jawaban dikirim.',
        ]);

        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->text('consent_data_description')->nullable(false)->change();
            $table->text('consent_cookie_description')->nullable(false)->change();
            $table->text('consent_withdrawal_description')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'consent_data_description',
                'consent_cookie_description',
                'consent_estimated_minutes',
                'consent_withdrawal_description',
                'research_contact',
                'quality_rules_version',
                'identical_answers_flag_enabled',
            ]);
        });
    }
};
