<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Domain\Study\PeriodStatus;
use App\Models\AuditEvent;
use App\Models\CalculationRun;
use Tests\Support\GoldenFixture;

beforeEach(function (): void {
    $this->eligibleRun = GoldenFixture::persistedClosedRun();
    $this->admin = $this->eligibleRun->creator;
    app(RecordMinimumSampleDeviation::class)->handle(
        $this->eligibleRun,
        'Penyimpangan minimum disetujui untuk fixture.',
        'Notulen fixture 2026-08-06',
        $this->admin,
    );
    $this->eligibleRun = $this->eligibleRun->fresh();
    $this->otherRun = app(CalculationRunService::class)->preview($this->eligibleRun->period, $this->admin);
});

it('locks one eligible run and period atomically', function (): void {
    $official = app(CalculationRunService::class)->lockAsOfficial($this->eligibleRun, $this->admin);

    expect($official->status)->toBe('official')
        ->and($official->period->status)->toBe(PeriodStatus::Locked)
        ->and($official->period->official_calculation_run_id)->toBe($official->id)
        ->and($official->locked_by)->toBe($this->admin->id)
        ->and($official->official_locked_at)->not->toBeNull();

    $this->assertDatabaseHas('audit_events', [
        'action' => 'calculation_run.locked_official',
        'auditable_type' => CalculationRun::class,
        'auditable_id' => $official->id,
        'actor_id' => $this->admin->id,
    ]);
});

it('rejects a second official run for the same period', function (): void {
    app(CalculationRunService::class)->lockAsOfficial($this->eligibleRun, $this->admin);

    expect(fn () => app(CalculationRunService::class)->lockAsOfficial($this->otherRun, $this->admin))
        ->toThrow(DomainException::class, 'Periode ini sudah mempunyai hasil resmi dan tidak dapat dikunci ulang.');
});

it('rolls back run period pointer status and audit when locking fails', function (): void {
    AuditEvent::creating(function (AuditEvent $event): void {
        if ($event->action === 'calculation_run.locked_official') {
            throw new RuntimeException('Simulated audit failure.');
        }
    });

    expect(fn () => app(CalculationRunService::class)->lockAsOfficial($this->eligibleRun, $this->admin))
        ->toThrow(RuntimeException::class, 'Simulated audit failure.');

    expect($this->eligibleRun->fresh()->status)->toBe('preview')
        ->and($this->eligibleRun->period->fresh()->status)->toBe(PeriodStatus::Closed)
        ->and($this->eligibleRun->period->fresh()->official_calculation_run_id)->toBeNull()
        ->and(AuditEvent::query()->where('action', 'calculation_run.locked_official')->count())->toBe(0);
});
