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

it('creates a new draft period from the admin study settings', function () {
    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->set('newPeriodName', 'Evaluasi Wong Reang Apps 2027')
        ->set('newPeriodOpensAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('newPeriodClosesAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('createPeriod')
        ->assertHasNoErrors();

    $period = EvaluationPeriod::query()->where('name', 'Evaluasi Wong Reang Apps 2027')->first();

    expect($period)->not->toBeNull()
        ->and($period->status)->toBe(PeriodStatus::Draft)
        ->and($period->slug)->toBe('evaluasi-wong-reang-apps-2027')
        ->and($period->configuration_locked_at)->toBeNull()
        ->and($period->official_calculation_run_id)->toBeNull();
});

it('rejects a duplicate slug when creating a period', function () {
    $existing = EvaluationPeriod::firstOrFail();

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->set('newPeriodName', $existing->name)
        ->set('newPeriodSlug', $existing->slug)
        ->call('createPeriod')
        ->assertHasErrors(['newPeriodSlug']);
});

it('does not change the existing periods when creating a new one', function () {
    $existing = EvaluationPeriod::firstOrFail();
    $before = $existing->getAttributes();

    Livewire::actingAs($this->admin)
        ->test(StudySettings::class)
        ->set('newPeriodName', 'Periode Tambahan')
        ->set('newPeriodOpensAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->set('newPeriodClosesAt', now()->addMonth()->format('Y-m-d\TH:i'))
        ->call('createPeriod');

    expect(EvaluationPeriod::query()->findOrFail($existing->id)->getAttributes())
        ->toEqual($before);
});
