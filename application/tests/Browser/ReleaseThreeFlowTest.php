<?php

use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;

it('exercises the complete release three admin analysis flow', function () {
    $this->seed(WongReangStudySeeder::class);
    UeqBenchmark::query()->update(['verified_at' => now()]);
    $period = EvaluationPeriod::firstOrFail();
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($admin);

    visit(route('admin.dashboard'))
        ->resize(1280, 800)
        ->assertSee('Dashboard progres')
        ->click('[data-flux-sidebar-item][href$="/admin/reports"]')
        ->waitForText('Laporan Agregat Penelitian (Bab IV)')
        ->assertSee('Ekspor XLSX Agregat')
        ->click('[data-flux-sidebar-item][href$="/admin/calculations"]')
        ->waitForText('Kalkulasi UEQ dan SAW')
        ->press('Jalankan preview')
        ->waitForText('Hasil UEQ per Modul')
        ->click('button[wire\\:click="lockOfficial"]')
        ->waitForText('OFFICIAL / LOCKED')
        ->assertSee('OFFICIAL / LOCKED');
});
