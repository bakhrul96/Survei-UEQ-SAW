<?php

use App\Application\Calculation\CalculationInputChangeRecorder;
use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Application\Quality\RecordExpertJudgment;
use App\Domain\Study\PeriodStatus;
use App\Models\AuditEvent;
use App\Models\CalculationRun;
use App\Models\EvaluationPeriod;
use App\Models\TechnicalInformant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Support\GoldenFixture;

it('increments revision stales previews and appends an audit event', function () {
    $period = EvaluationPeriod::factory()->create(['calculation_input_revision' => 4]);
    $actor = User::factory()->create();
    $run = pendingCalculationRun($period, $actor);

    DB::transaction(function () use ($period, $actor): void {
        app(CalculationInputChangeRecorder::class)->record(
            $period,
            $actor,
            'technical_assessment.updated',
            TechnicalInformant::class,
            27,
            ['anonymous_code' => 'TI-01'],
            ['anonymous_code' => 'TI-01', 'weights' => ['c1' => 40, 'c2' => 30, 'c3' => 30]],
        );
    });

    expect($period->fresh()->calculation_input_revision)->toBe(5)
        ->and($run->fresh()->status)->toBe('stale');
    $this->assertDatabaseHas('audit_events', [
        'action' => 'technical_assessment.updated',
        'actor_id' => $actor->id,
        'auditable_type' => TechnicalInformant::class,
        'auditable_id' => 27,
    ]);
});

it('never stales or mutates an official run when a later input event is recorded', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $actor = $run->creator;
    app(RecordExpertJudgment::class)->handle(
        $run,
        $run->sawResults->firstOrFail()->unit,
        1,
        'Backlog fixture.',
        $actor,
    );
    app(RecordMinimumSampleDeviation::class)->handle($run, 'Alasan fixture.', 'Notulen fixture.', $actor);
    $official = app(CalculationRunService::class)->lockAsOfficial($run->fresh(), $actor);
    $evidenceBefore = [
        'hash' => $official->input_hash,
        'ueq' => $official->ueqResults()->count(),
        'saw' => $official->sawResults()->count(),
        'sensitivity' => $official->sensitivityResults()->count(),
        'backlog' => $official->expertJudgments()->count(),
    ];

    DB::transaction(function () use ($official, $actor): void {
        app(CalculationInputChangeRecorder::class)->record(
            $official->period,
            $actor,
            'technical_assessment.updated',
            TechnicalInformant::class,
            99,
            null,
            ['anonymous_code' => 'TI-later'],
        );
    });

    expect($official->fresh()->status)->toBe('official')
        ->and($official->period->fresh()->status)->toBe(PeriodStatus::Locked)
        ->and($official->fresh()->input_hash)->toBe($evidenceBefore['hash'])
        ->and($official->ueqResults()->count())->toBe($evidenceBefore['ueq'])
        ->and($official->sawResults()->count())->toBe($evidenceBefore['saw'])
        ->and($official->sensitivityResults()->count())->toBe($evidenceBefore['sensitivity'])
        ->and($official->expertJudgments()->count())->toBe($evidenceBefore['backlog']);
});

it('rolls back revision stale status and audit together', function () {
    $period = EvaluationPeriod::factory()->create(['calculation_input_revision' => 4]);
    $actor = User::factory()->create();
    $run = pendingCalculationRun($period, $actor);

    try {
        DB::transaction(function () use ($period, $actor): void {
            app(CalculationInputChangeRecorder::class)->record(
                $period,
                $actor,
                'technical_assessment.updated',
                TechnicalInformant::class,
                27,
                null,
                ['anonymous_code' => 'TI-01'],
            );

            throw new RuntimeException('Simulated mutation failure.');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Simulated mutation failure.');
    }

    expect($period->fresh()->calculation_input_revision)->toBe(4)
        ->and($run->fresh()->status)->toBe('preview')
        ->and(AuditEvent::count())->toBe(0);
});

function pendingCalculationRun(EvaluationPeriod $period, User $actor): CalculationRun
{
    return CalculationRun::query()->create([
        'evaluation_period_id' => $period->id,
        'algorithm_version' => CalculationRunService::ALGORITHM_VERSION,
        'status' => 'preview',
        'input_hash' => str_repeat('a', 64),
        'input_snapshot' => [],
        'warnings' => [],
        'included_count' => 0,
        'excluded_count' => 0,
        'created_by' => $actor->id,
        'calculated_at' => now(),
    ]);
}
