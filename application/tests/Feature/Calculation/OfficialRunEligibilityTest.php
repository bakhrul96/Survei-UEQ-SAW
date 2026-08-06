<?php

use App\Application\Calculation\OfficialRunEligibility;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Domain\Study\PeriodStatus;
use App\Models\CalculationRun;
use App\Models\SensitivityResult;
use Illuminate\Support\Facades\DB;
use Tests\Support\GoldenFixture;

/** @param callable(array<string, mixed>): array<string, mixed> $mutate */
function mutateRunSnapshot(CalculationRun $run, callable $mutate): CalculationRun
{
    DB::table('calculation_runs')->where('id', $run->id)->update([
        'input_snapshot' => json_encode($mutate($run->input_snapshot), JSON_THROW_ON_ERROR),
    ]);

    return $run->fresh(['period', 'sawResults', 'sensitivityResults']);
}

it('rejects official lock eligibility for a draft period', function (): void {
    $run = GoldenFixture::persistedRun();
    $run->period->update(['status' => PeriodStatus::Draft]);

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Periode harus berstatus closed sebelum hasil resmi dikunci.');
});

it('rejects a run that is not preview', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $run->update(['status' => 'stale']);

    expect(app(OfficialRunEligibility::class)->issues($run->fresh()))
        ->toContain('Hanya calculation run berstatus preview yang dapat dikunci.');
});

it('rejects a snapshot from an obsolete input revision', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $run->period->increment('calculation_input_revision');

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Revision input snapshot tidak sama dengan revision periode saat ini.');
});

it('rejects included submissions without the exact item keys one through twenty six', function (): void {
    $run = mutateRunSnapshot(GoldenFixture::persistedClosedRun(), function (array $snapshot): array {
        $submissionId = (string) $snapshot['included_submission_ids'][0];
        unset($snapshot['included_raw_answers'][$submissionId]['26']);
        $snapshot['included_raw_answers'][$submissionId]['27'] = 4;

        return $snapshot;
    });

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Setiap submission included harus memiliki jawaban item tepat 1 sampai 26.');
});

it('rejects unreviewed quality decisions', function (): void {
    $run = mutateRunSnapshot(GoldenFixture::persistedClosedRun(), function (array $snapshot): array {
        $snapshot['quality_decisions'][0]['decision'] = 'unreviewed';

        return $snapshot;
    });

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Semua submission harus memiliki keputusan kualitas included atau excluded.');
});

it('requires an approved deviation for units below the minimum sample', function (): void {
    $run = GoldenFixture::persistedClosedRun();

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('ibadah-yu baru memiliki 4 dari minimum 20 respons included.')
        ->toContain('info-yu baru memiliki 4 dari minimum 20 respons included.');
});

it('rejects incomplete or out of range technical consensus', function (array $replacement): void {
    $run = mutateRunSnapshot(GoldenFixture::persistedClosedRun(), function (array $snapshot) use ($replacement): array {
        $snapshot['technical_consensus'] = array_replace($snapshot['technical_consensus'], $replacement);

        return $snapshot;
    });

    expect(app(OfficialRunEligibility::class)->issues($run))
        ->toContain('Konsensus teknis lengkap dari 3 sampai 5 informan wajib tersedia.');
})->with([
    'incomplete' => [['is_complete' => false]],
    'too few informants' => [['informant_count' => 2]],
    'too many informants' => [['informant_count' => 6]],
]);

it('rejects missing algorithm and benchmark provenance', function (): void {
    $run = mutateRunSnapshot(GoldenFixture::persistedClosedRun(), function (array $snapshot): array {
        $snapshot['benchmarks'][0]['source'] = '';
        $snapshot['benchmarks'][0]['version'] = '';

        return $snapshot;
    });
    DB::table('calculation_runs')->where('id', $run->id)->update(['algorithm_version' => '']);

    expect(app(OfficialRunEligibility::class)->issues($run->fresh()))
        ->toContain('Versi algoritma wajib tercatat pada calculation run.')
        ->toContain('Setiap benchmark harus memiliki source dan version.');
});

it('rejects empty or incomplete analytical results', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    DB::table('saw_results')->where('calculation_run_id', $run->id)->limit(1)->delete();
    SensitivityResult::query()->where('calculation_run_id', $run->id)->limit(1)->delete();

    expect(app(OfficialRunEligibility::class)->issues($run->fresh()))
        ->toContain('Minimal dua alternatif SAW lengkap diperlukan.')
        ->toContain('Hasil sensitivitas S0, S1, dan S2 harus lengkap untuk setiap alternatif.');
});

it('accepts a complete closed run with an approved minimum deviation', function (): void {
    $run = GoldenFixture::persistedClosedRun();
    $actor = $run->creator;
    app(RecordMinimumSampleDeviation::class)->handle(
        $run,
        'Pengumpulan data dihentikan sesuai keputusan pembimbing.',
        'Notulen bimbingan 2026-08-06',
        $actor,
    );

    expect(app(OfficialRunEligibility::class)->issues($run->fresh()))->toBe([]);
});
