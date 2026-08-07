<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\User;

it('does not force a permanent dark class on the admin layout root', function () {
    $admin = User::factory()->create(['name' => 'Admin', 'email_verified_at' => now()]);
    auth()->login($admin);

    $html = view('layouts.app.sidebar', ['slot' => new \Illuminate\Support\HtmlString('x')])->render();

    expect($html)->not->toContain('class="dark"');
});

it('does not force a permanent dark class on the survey layout root', function () {
    $period = EvaluationPeriod::factory()->create([
        'status' => PeriodStatus::Active,
        'configuration_locked_at' => now(),
        'opens_at' => now()->subDay(),
        'closes_at' => now()->addMonth(),
    ]);
    $period = lockStudyConfiguration($period);

    $this->get(route('survey.consent', $period))
        ->assertOk()
        ->assertDontSee('class="dark"', escape: false);
});

it('renders a one-tap appearance toggle in the admin chrome', function () {
    $admin = User::factory()->create(['name' => 'Admin', 'email_verified_at' => now()]);
    auth()->login($admin);

    $sidebar = view('layouts.app.sidebar', ['slot' => new \Illuminate\Support\HtmlString('x')])->render();
    $header = view('layouts.app.header', ['slot' => new \Illuminate\Support\HtmlString('x')])->render();

    expect($sidebar.$header)
        ->toContain('data-appearance-toggle')
        ->toContain('$flux.dark = ! $flux.dark');
});
