<?php

use App\Models\User;
use Livewire\Livewire;

it('shows a friendly empty state instead of 404 when no period exists', function (string $component, string $marker) {
    \App\Models\EvaluationPeriod::query()->delete();
    $admin = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($admin)
        ->test($component)
        ->assertSee($marker);
})->with([
    'dashboard' => ['App\Livewire\Admin\Dashboard', 'Belum ada periode'],
    'responses' => ['App\Livewire\Admin\Responses', 'Belum ada periode'],
    'calculations' => ['App\Livewire\Admin\Calculations', 'Belum ada periode'],
    'reports' => ['App\Livewire\Admin\Reports', 'Belum ada periode'],
    'technical-assessments' => ['App\Livewire\Admin\TechnicalAssessments', 'Belum ada periode'],
    'study-settings' => ['App\Livewire\Admin\StudySettings', 'Buat periode baru'],
]);
