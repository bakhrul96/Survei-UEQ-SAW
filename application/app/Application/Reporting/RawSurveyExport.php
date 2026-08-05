<?php

namespace App\Application\Reporting;

use App\Models\EvaluationPeriod;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class RawSurveyExport
{
    /** @var list<string> */
    private const HEADERS = [
        'submission_id', 'respondent_code', 'unit_code', 'unit_name', 'instrument_version',
        'started_at', 'completed_at', 'duration_seconds', 'session_sequence',
        'item_01', 'item_02', 'item_03', 'item_04', 'item_05', 'item_06', 'item_07', 'item_08', 'item_09',
        'item_10', 'item_11', 'item_12', 'item_13', 'item_14', 'item_15', 'item_16', 'item_17', 'item_18',
        'item_19', 'item_20', 'item_21', 'item_22', 'item_23', 'item_24', 'item_25', 'item_26',
    ];

    private const ITEM_COLUMNS_SQL = <<<'SQL'
MAX(CASE WHEN answers.item_order = 1 THEN answers.raw_score END) as item_01,
MAX(CASE WHEN answers.item_order = 2 THEN answers.raw_score END) as item_02,
MAX(CASE WHEN answers.item_order = 3 THEN answers.raw_score END) as item_03,
MAX(CASE WHEN answers.item_order = 4 THEN answers.raw_score END) as item_04,
MAX(CASE WHEN answers.item_order = 5 THEN answers.raw_score END) as item_05,
MAX(CASE WHEN answers.item_order = 6 THEN answers.raw_score END) as item_06,
MAX(CASE WHEN answers.item_order = 7 THEN answers.raw_score END) as item_07,
MAX(CASE WHEN answers.item_order = 8 THEN answers.raw_score END) as item_08,
MAX(CASE WHEN answers.item_order = 9 THEN answers.raw_score END) as item_09,
MAX(CASE WHEN answers.item_order = 10 THEN answers.raw_score END) as item_10,
MAX(CASE WHEN answers.item_order = 11 THEN answers.raw_score END) as item_11,
MAX(CASE WHEN answers.item_order = 12 THEN answers.raw_score END) as item_12,
MAX(CASE WHEN answers.item_order = 13 THEN answers.raw_score END) as item_13,
MAX(CASE WHEN answers.item_order = 14 THEN answers.raw_score END) as item_14,
MAX(CASE WHEN answers.item_order = 15 THEN answers.raw_score END) as item_15,
MAX(CASE WHEN answers.item_order = 16 THEN answers.raw_score END) as item_16,
MAX(CASE WHEN answers.item_order = 17 THEN answers.raw_score END) as item_17,
MAX(CASE WHEN answers.item_order = 18 THEN answers.raw_score END) as item_18,
MAX(CASE WHEN answers.item_order = 19 THEN answers.raw_score END) as item_19,
MAX(CASE WHEN answers.item_order = 20 THEN answers.raw_score END) as item_20,
MAX(CASE WHEN answers.item_order = 21 THEN answers.raw_score END) as item_21,
MAX(CASE WHEN answers.item_order = 22 THEN answers.raw_score END) as item_22,
MAX(CASE WHEN answers.item_order = 23 THEN answers.raw_score END) as item_23,
MAX(CASE WHEN answers.item_order = 24 THEN answers.raw_score END) as item_24,
MAX(CASE WHEN answers.item_order = 25 THEN answers.raw_score END) as item_25,
MAX(CASE WHEN answers.item_order = 26 THEN answers.raw_score END) as item_26
SQL;

    public function spreadsheet(EvaluationPeriod $period): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Raw UEQ');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;
        foreach ($this->rows($period) as $submission) {
            $sheet->fromArray([
                $submission->submissionId,
                'R-'.str_pad((string) $submission->profileId, 6, '0', STR_PAD_LEFT),
                $submission->unitCode,
                $submission->unitName,
                $submission->instrumentVersion,
                $submission->startedAt,
                $submission->completedAt,
                $submission->durationSeconds,
                $submission->sessionSequence,
                ...$submission->scores,
            ], null, "A{$row}");
            $row++;
        }

        return $spreadsheet;
    }

    /** @return \Generator<int, RawSurveyExportRow> */
    private function rows(EvaluationPeriod $period): \Generator
    {
        $records = DB::table('survey_submissions as submissions')
            ->join('respondent_profiles as profiles', function ($join) {
                $join->on('profiles.evaluation_period_id', '=', 'submissions.evaluation_period_id')
                    ->on('profiles.anonymous_respondent_id', '=', 'submissions.anonymous_respondent_id');
            })
            ->join('evaluation_units as units', 'units.id', '=', 'submissions.evaluation_unit_id')
            ->leftJoin('survey_answers as answers', 'answers.survey_submission_id', '=', 'submissions.id')
            ->where('submissions.evaluation_period_id', $period->id)
            ->where('submissions.status', 'submitted')
            ->select([
                'submissions.id as submission_id', 'profiles.id as profile_id', 'units.code as unit_code', 'units.name as unit_name',
                'submissions.instrument_version', 'submissions.started_at', 'submissions.completed_at',
                'submissions.duration_seconds', 'submissions.session_sequence',
            ])
            ->selectRaw(self::ITEM_COLUMNS_SQL)
            ->groupBy(
                'submissions.id', 'profiles.id', 'units.code', 'units.name', 'submissions.instrument_version',
                'submissions.started_at', 'submissions.completed_at', 'submissions.duration_seconds', 'submissions.session_sequence',
            )
            ->orderBy('submissions.id')
            ->cursor();

        foreach ($records as $record) {
            yield RawSurveyExportRow::fromRecord($record);
        }
    }
}
