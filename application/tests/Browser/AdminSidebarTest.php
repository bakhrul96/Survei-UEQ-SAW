<?php

use App\Models\User;
use Database\Seeders\WongReangStudySeeder;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
    $this->actingAs($this->admin);
});

it('shows the complete sidebar on desktop', function () {
    visit(route('admin.dashboard'))
        ->resize(1280, 800)
        ->assertVisible('[data-flux-sidebar]')
        ->assertVisible('a[href*="/admin/study"]')
        ->assertSee('Pengumpulan Data')
        ->assertSee('Laporan & Ekspor')
        ->assertSee('Penilaian Teknis')
        ->assertDontSee('Repository')
        ->assertDontSee('Documentation')
        ->assertScript('document.querySelector(\'a[data-flux-sidebar-item][href*="/admin/dashboard"]\').hasAttribute(\'data-current\')');
});

it('opens the complete sidebar from the hamburger at 360 pixels', function () {
    visit(route('admin.dashboard'))
        ->resize(360, 800)
        ->assertVisible('[data-flux-sidebar-toggle]')
        ->assertAttribute('[data-flux-sidebar-toggle]', 'aria-label', 'Toggle sidebar')
        ->click('[data-flux-sidebar-toggle]')
        ->assertVisible('a[href*="/admin/study"]')
        ->assertSee('Pengaturan Studi')
        ->assertSee('Pengaturan Akun');
});
