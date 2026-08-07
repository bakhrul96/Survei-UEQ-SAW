<?php

use App\Domain\Study\PeriodStatus;
use App\Livewire\Admin\StudySettings;
use App\Models\EvaluationPeriod;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create(['email_verified_at' => now()]);
});

it('always shows the shareable survey link for the current period at the top of study settings', function () {
    $period = EvaluationPeriod::firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        // Tautan share untuk periode yang sedang dikelola selalu tampil, bukan hanya setelah create.
        ->assertSee('/s/wong-reang/'.$period->slug);
});

it('shows the shareable link for an active period', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Active]);

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->assertSee('/s/wong-reang/'.$period->slug);
});
