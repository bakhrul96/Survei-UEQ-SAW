<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Application\Calculation\SensitivityResultWriter;
use App\Application\Quality\RecordExpertJudgment;
use App\Models\CalculationRun;
use Tests\Support\GoldenFixture;
use Tests\Support\ReleaseTwoFixture;

it('prevents saw and pooled result mutation or deletion', function (): void {
    $scenario = ReleaseTwoFixture::scenario();
    $run = app(CalculationRunService::class)->preview($scenario->period, $scenario->admin);
    $sawResult = $run->sawResults->firstOrFail();
    $pooledResult = $run->ueqPooledResults->firstOrFail();

    expect(fn () => $sawResult->update(['preference_value' => 0.0]))
        ->toThrow(LogicException::class, 'SAW results are immutable.')
        ->and(fn () => $sawResult->delete())
        ->toThrow(LogicException::class, 'SAW results are immutable.')
        ->and(fn () => $pooledResult->update(['cronbach_alpha' => 0.0]))
        ->toThrow(LogicException::class, 'UEQ pooled results are immutable.')
        ->and(fn () => $pooledResult->delete())
        ->toThrow(LogicException::class, 'UEQ pooled results are immutable.');
});

it('prevents sensitivity result mutation deletion and writer replacement', function (): void {
    $run = GoldenFixture::persistedRun();
    $result = $run->sensitivityResults->firstOrFail();

    expect(fn () => $result->update(['rank' => 99]))
        ->toThrow(LogicException::class, 'Sensitivity results are immutable.')
        ->and(fn () => $result->delete())
        ->toThrow(LogicException::class, 'Sensitivity results are immutable.')
        ->and(fn () => app(SensitivityResultWriter::class)->write($run, []))
        ->toThrow(LogicException::class, 'Sensitivity results can only be written once.');
});

it('prevents official calculation runs and their expert judgments from changing', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $actor = $run->creator;
    $judgment = app(RecordExpertJudgment::class)->handle(
        $run,
        $run->sawResults->firstOrFail()->unit,
        1,
        'Urutan awal fixture.',
        $actor,
    );
    app(RecordMinimumSampleDeviation::class)->handle($run, 'Alasan fixture.', 'Notulen fixture.', $actor);
    $official = app(CalculationRunService::class)->lockAsOfficial($run->fresh(), $actor);

    expect(fn () => $official->update(['status' => 'archived']))
        ->toThrow(LogicException::class, 'Official calculation runs are immutable.')
        ->and(fn () => $judgment->update(['reason' => 'Diubah']))
        ->toThrow(LogicException::class, 'Official expert judgments are immutable.')
        ->and(fn () => $judgment->delete())
        ->toThrow(LogicException::class, 'Official expert judgments are immutable.');

    expect(CalculationRun::query()->findOrFail($official->id)->status)->toBe('official');
});
