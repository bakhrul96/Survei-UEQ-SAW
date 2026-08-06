<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\IOFactory;

function rawExportAdmin(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ]);
}

it('exports traceable period metadata and 26 UEQ items to XLSX without private identifiers', function () {
    $fixture = completedSubmissionFixture();

    $response = $this->actingAs(rawExportAdmin())->get(route('admin.exports.raw.xlsx', $fixture->period));
    $response->assertOk();

    $content = $response->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'ueq-export-');
    file_put_contents($path, $content);
    $sheet = IOFactory::load($path)->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:AN1')[0];
    $values = $sheet->rangeToArray('A2:AN2')[0];

    expect($headers)->toBe([
        'period_id', 'period_slug', 'period_name', 'period_status', 'exported_at',
        'submission_id', 'respondent_code', 'unit_code', 'unit_name', 'instrument_version',
        'started_at', 'completed_at', 'duration_seconds', 'session_sequence',
        ...array_map(fn (int $order): string => 'item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT), range(1, 26)),
    ])->and((int) $values[0])->toBe($fixture->period->id)
        ->and($values[1])->toBe($fixture->period->slug)
        ->and($values[2])->toBe($fixture->period->name)
        ->and($values[3])->toBe($fixture->period->status->value)
        ->and(CarbonImmutable::parse($values[4])->toIso8601String())->toBe($values[4]);

    expect($content)->not->toContain('token_hash', 'idempotency_key', 'anonymous_respondent_id');

    unlink($path);
});

it('exports the same traceability metadata to CSV without private profile data', function () {
    $fixture = completedSubmissionFixture();

    $response = $this->actingAs(rawExportAdmin())->get(route('admin.exports.raw.csv', $fixture->period));
    $response->assertOk();

    $content = $response->streamedContent();
    $lines = preg_split('/\r\n|\r|\n/', trim($content));
    $headers = str_getcsv(ltrim($lines[0], "\xEF\xBB\xBF"));
    $values = str_getcsv($lines[1]);

    expect($headers)->toBe([
        'period_id', 'period_slug', 'period_name', 'period_status', 'exported_at',
        'submission_id', 'respondent_code', 'unit_code', 'unit_name', 'instrument_version',
        'started_at', 'completed_at', 'duration_seconds', 'session_sequence',
        ...array_map(fn (int $order): string => 'item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT), range(1, 26)),
    ])->and((int) $values[0])->toBe($fixture->period->id)
        ->and($values[1])->toBe($fixture->period->slug)
        ->and($values[2])->toBe($fixture->period->name)
        ->and($values[3])->toBe($fixture->period->status->value)
        ->and(CarbonImmutable::parse($values[4])->toIso8601String())->toBe($values[4]);

    expect($content)->not->toContain('token_hash', 'idempotency_key', 'age', 'anonymous_respondent_id');
});
