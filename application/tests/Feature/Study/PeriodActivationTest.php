<?php

use App\Domain\Study\PeriodReadinessService;
use App\Domain\Study\PeriodStatus;
use App\Livewire\Admin\StudySettings;
use App\Models\EvaluationPeriod;
use App\Models\UeqBenchmark;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use DomainException;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
});

it('rejects activation while instrument and benchmarks are unverified', function () {
    $period = EvaluationPeriod::firstOrFail();

    $issues = app(PeriodReadinessService::class)->issues($period);

    expect($issues)->toContain('Instrumen UEQ belum diverifikasi.')
        ->and($issues)->toContain('Enam benchmark belum diverifikasi.');
});

it('locks configuration when every readiness rule passes', function () {
    $period = EvaluationPeriod::firstOrFail();
    $period->update([
        'instrument_source' => 'UEQ Bahasa Indonesia terverifikasi',
        'instrument_verified_at' => now(),
        'opens_at' => now(),
        'closes_at' => now()->addMonth(),
    ]);
    UeqBenchmark::query()->update(['verified_at' => now()]);

    $activated = app(PeriodReadinessService::class)->activate($period->fresh());

    expect($activated->status)->toBe(PeriodStatus::Active)
        ->and($activated->configuration_locked_at)->not->toBeNull();
});

it('does not activate when readiness issues exist', function () {
    $period = EvaluationPeriod::firstOrFail();

    expect(fn () => app(PeriodReadinessService::class)->activate($period))
        ->toThrow(DomainException::class, 'Instrumen UEQ belum diverifikasi.');

    expect($period->fresh()->status)->toBe(PeriodStatus::Draft)
        ->and($period->fresh()->configuration_locked_at)->toBeNull();
});

it('requires login for settings', function () {
    $this->get(route('admin.study-settings'))->assertRedirect('/login');
});

it('does not let an active period change locked fields', function () {
    $admin = User::factory()->create();
    $period = EvaluationPeriod::firstOrFail();
    $period->update(['status' => PeriodStatus::Active, 'configuration_locked_at' => now()]);

    Livewire::actingAs($admin)->test(StudySettings::class)
        ->set('minimumPerUnit', 99)
        ->call('save')
        ->assertForbidden();

    expect($period->fresh()->minimum_per_unit)->toBe(20);
});
