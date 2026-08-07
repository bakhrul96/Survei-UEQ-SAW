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

it('shows a non-clickable activation notice for a draft period share link', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Draft]);

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->assertSee('/s/wong-reang/'.$period->slug)
        ->assertSee('Aktif setelah periode diaktifkan')
        // Draft: tautan bukan <a href> yang bisa diklik.
        ->assertDontSee('href="'.url('/s/wong-reang/'.$period->slug).'"', escape: false);
});

it('shows a clickable link for an active period share link', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Active]);

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->assertSee('href="'.url('/s/wong-reang/'.$period->slug).'"', escape: false);
});
