<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Livewire\Livewire;

it('renders every admin menu page content for a logged-in admin', function (string $component, string $marker) {
    EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);
    $admin = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($admin)
        ->test($component)
        ->assertSee($marker);
})->with([
    'dashboard' => ['App\Livewire\Admin\Dashboard', 'Responden unik'],
    'responses' => ['App\Livewire\Admin\Responses', 'Review kualitas respons'],
    'calculations' => ['App\Livewire\Admin\Calculations', 'Kalkulasi UEQ dan SAW'],
    'reports' => ['App\Livewire\Admin\Reports', 'Laporan Agregat Penelitian'],
    'technical-assessments' => ['App\Livewire\Admin\TechnicalAssessments', 'Informan teknis'],
    'study-settings' => ['App\Livewire\Admin\StudySettings', 'Pengaturan Studi'],
]);
