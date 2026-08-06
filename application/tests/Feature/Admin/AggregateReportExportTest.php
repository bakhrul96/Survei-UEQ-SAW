<?php

use App\Application\Calculation\CalculationRunService;
use App\Application\Calculation\RecordMinimumSampleDeviation;
use App\Domain\Study\PeriodStatus;
use App\Models\CalculationRun;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\GoldenFixture;

beforeEach(function (): void {
    $this->run = GoldenFixture::persistedRun();
    $this->admin = $this->run->creator;
    $this->admin->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();
    $this->period = $this->run->period;
});

it('exports a complete flat aggregate csv', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.exports.aggregate.csv', $this->period));

    $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $csv = $response->streamedContent();
    $lines = preg_split('/\r\n|\r|\n/', ltrim($csv, "\xEF\xBB\xBF"), -1, PREG_SPLIT_NO_EMPTY);
    $rows = array_map(fn (string $line): array => str_getcsv($line, ',', '"', '\\'), $lines ?: []);
    $header = array_shift($rows);
    $sections = array_values(array_unique(array_column($rows, 0)));

    expect($header)->toBe([
        'section', 'period_name', 'instrument_version', 'benchmark_version', 'benchmark_source',
        'run_id', 'run_status', 'generated_at', 'unit_code', 'unit_name', 'scale', 'scenario',
        'metric', 'value', 'rank', 'delta_rank', 'reason',
    ])->and($sections)->toContain('metadata', 'benchmark', 'ueq', 'saw', 'sensitivity', 'operational_backlog')
        ->and($csv)->toContain($this->period->instrument_version)
        ->toContain((string) $this->run->id)
        ->toContain($this->run->status)
        ->toContain('S1')
        ->toContain('delta_rank');
});

it('exports complete xlsx sheets metadata provenance and ueq uncertainty', function (): void {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.exports.aggregate.xlsx', $this->period));
    $response->assertOk();

    $tempFile = tempnam(sys_get_temp_dir(), 'agg_export_');
    file_put_contents($tempFile, $response->streamedContent());
    $spreadsheet = IOFactory::load($tempFile);
    unlink($tempFile);

    expect($spreadsheet->getSheetNames())->toBe([
        'Metadata Run', 'Benchmark', 'Hasil UEQ', 'Peringkat SAW', 'Analisis Sensitivitas', 'Backlog Operasional',
    ]);
    $metadata = collect($spreadsheet->getSheetByName('Metadata Run')->toArray())->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);
    $benchmark = $spreadsheet->getSheetByName('Benchmark')->toArray();
    $ueqHeader = $spreadsheet->getSheetByName('Hasil UEQ')->rangeToArray('A1:K1')[0];
    $snapshotBenchmark = $this->run->input_snapshot['benchmarks'][0];

    expect((int) $metadata['Run ID'])->toBe($this->run->id)
        ->and($benchmark[1][0])->toBe($snapshotBenchmark['version'])
        ->and($benchmark[1][3])->toBe($snapshotBenchmark['source'])
        ->and($ueqHeader)->toContain('SE', 'CI 95% Lower', 'CI 95% Upper');
});

it('keeps both formats pinned to the official pointer when a newer preview exists', function (): void {
    $this->period->update(['status' => PeriodStatus::Closed]);
    app(RecordMinimumSampleDeviation::class)->handle($this->run, 'Alasan fixture.', 'Notulen fixture.', $this->admin);
    $official = app(CalculationRunService::class)->lockAsOfficial($this->run->fresh(), $this->admin);
    $newerPreview = app(CalculationRunService::class)->preview($official->period, $this->admin);

    $csvResponse = $this->actingAs($this->admin)->get(route('admin.exports.aggregate.csv', $this->period));
    $csvRows = array_map(
        fn (string $line): array => str_getcsv($line, ',', '"', '\\'),
        preg_split('/\r\n|\r|\n/', ltrim($csvResponse->streamedContent(), "\xEF\xBB\xBF"), -1, PREG_SPLIT_NO_EMPTY) ?: [],
    );
    $runIds = collect(array_slice($csvRows, 1))->pluck(5)->filter()->unique()->values()->all();

    $xlsxResponse = $this->actingAs($this->admin)->get(route('admin.exports.aggregate.xlsx', $this->period));
    $tempFile = tempnam(sys_get_temp_dir(), 'agg_official_');
    file_put_contents($tempFile, $xlsxResponse->streamedContent());
    $book = IOFactory::load($tempFile);
    unlink($tempFile);
    $metadata = collect($book->getSheetByName('Metadata Run')->toArray())->mapWithKeys(fn (array $row): array => [$row[0] => $row[1]]);

    expect($runIds)->toBe([(string) $official->id])
        ->and((int) $metadata['Run ID'])->toBe($official->id)
        ->and($official->id)->not->toBe($newerPreview->id)
        ->and(CalculationRun::query()->findOrFail($official->id)->status)->toBe('official');
});
