<?php

namespace Tests\Unit\Reporting;

use App\Application\Reporting\RawSurveyExportRow;
use PHPUnit\Framework\TestCase;

class RawSurveyExportRowTest extends TestCase
{
    public function test_it_normalizes_a_database_export_record(): void
    {
        $record = (object) array_merge([
            'submission_id' => '12',
            'profile_id' => '7',
            'unit_code' => 'ibadah-yu',
            'unit_name' => 'Ibadah-Yu',
            'instrument_version' => 'UEQ-ID-26-v1',
            'started_at' => '2026-08-05 08:00:00',
            'completed_at' => '2026-08-05 08:04:00',
            'duration_seconds' => '240.000000',
            'session_sequence' => '3',
        ], collect(range(1, 26))
            ->mapWithKeys(fn (int $order): array => ['item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT) => (string) $order])
            ->all());

        $row = RawSurveyExportRow::fromRecord($record);

        $this->assertSame(12, $row->submissionId);
        $this->assertSame(7, $row->profileId);
        $this->assertSame('ibadah-yu', $row->unitCode);
        $this->assertSame(240, $row->durationSeconds);
        $this->assertSame(3, $row->sessionSequence);
        $this->assertSame(range(1, 26), $row->scores);
    }
}
