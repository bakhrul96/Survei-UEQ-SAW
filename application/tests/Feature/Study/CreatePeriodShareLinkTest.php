<?php

use App\Livewire\Admin\StudySettings;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create(['email_verified_at' => now()]);
});

it('shows the shareable survey link after creating a new draft period', function () {
    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->set('newPeriodName', 'Evaluasi Wong Reang Apps 2027')
        ->set('newPeriodOpensAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('newPeriodClosesAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('createPeriod')
        ->assertHasNoErrors()
        // Link untuk dibagikan ke responden anonim harus ditampilkan.
        ->assertSee('/s/wong-reang/evaluasi-wong-reang-apps-2027');
});
