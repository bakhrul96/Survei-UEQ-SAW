<?php

use App\Models\EvaluationPeriod;
use App\Models\User;
use Database\Seeders\WongReangStudySeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function () {
    $this->seed(WongReangStudySeeder::class);
    $this->admin = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
    $this->period = EvaluationPeriod::firstOrFail();
});

it('exports aggregate report as xlsx with multi-sheet structure', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.exports.aggregate.xlsx', $this->period));

    $response->assertOk();

    $tempFile = tempnam(sys_get_temp_dir(), 'agg_export_');
    file_put_contents($tempFile, $response->streamedContent());

    $spreadsheet = IOFactory::load($tempFile);
    $sheetNames = $spreadsheet->getSheetNames();

    expect($sheetNames)->toContain('Metadata Run', 'Hasil UEQ', 'Peringkat SAW', 'Analisis Sensitivitas', 'Backlog Operasional');

    unlink($tempFile);
});

it('exports aggregate report as csv', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.exports.aggregate.csv', $this->period));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
