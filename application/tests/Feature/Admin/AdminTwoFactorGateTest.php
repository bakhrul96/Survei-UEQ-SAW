<?php

use App\Domain\Study\PeriodStatus;
use App\Models\EvaluationPeriod;
use App\Models\User;

it('lets an admin without 2fa reach the admin area instead of being forced to security settings', function () {
    EvaluationPeriod::factory()->create(['status' => PeriodStatus::Active]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard');
});
