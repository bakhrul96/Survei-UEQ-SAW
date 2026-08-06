<?php

use App\Livewire\Admin\Reports;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
    $this->period = EvaluationPeriod::firstOrFail();
});

it('requires authentication for reports page', function () {
    $this->get('/admin/reports')->assertRedirect('/login');
});

it('renders reports page for authenticated admin', function () {
    Livewire::actingAs($this->admin)
        ->test(Reports::class, ['periodId' => $this->period->id])
        ->assertSee('Laporan Agregat Penelitian (Bab IV)')
        ->assertSee('Status Run Acuan Laporan');
});
