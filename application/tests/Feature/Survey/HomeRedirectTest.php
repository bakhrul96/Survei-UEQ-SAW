<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;

it('redirects the home page to the active survey period entry', function () {
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'slug' => 'wong-reang-2026',
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
        'configuration_locked_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertRedirect(route('survey.entry', $period));
});

it('shows a friendly landing page when no active survey period exists', function () {
    EvaluationPeriod::query()->delete();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Survei');
});
