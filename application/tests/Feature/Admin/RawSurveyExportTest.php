<?php

use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

it('exports 26 item columns without token hashes', function () {
    $fixture = completedSubmissionFixture();
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.exports.raw.xlsx', $fixture->period));
    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'ueq-export-');
    file_put_contents($path, $response->streamedContent());
    $sheet = IOFactory::load($path)->getActiveSheet();
    $headers = $sheet->rangeToArray('A1:AI1')[0];

    expect($headers)->toContain('respondent_code', 'unit_code', 'item_01', 'item_26')
        ->and($headers)->not->toContain('token_hash');

    unlink($path);
});
