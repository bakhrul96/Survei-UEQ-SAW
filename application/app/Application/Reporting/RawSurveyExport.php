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

    public function spreadsheet(EvaluationPeriod $period): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Raw UEQ');
        $sheet->fromArray(self::HEADERS, null, 'A1');

        $row = 2;
        foreach ($this->rows($period) as $submission) {
            $sheet->fromArray([
                $submission->submission_id,
                'R-'.str_pad((string) $submission->profile_id, 6, '0', STR_PAD_LEFT),
                $submission->unit_code,
                $submission->unit_name,
                $submission->instrument_version,
                $submission->started_at,
                $submission->completed_at,
                (int) $submission->duration_seconds,
                (int) $submission->session_sequence,
                ...array_map(fn (int $order): int => (int) $submission->{'item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT)}, range(1, 26)),
            ], null, "A{$row}");
            $row++;
        }

        return $spreadsheet;
    }

    /** @return \Generator<int, object> */
    private function rows(EvaluationPeriod $period): \Generator
    {
        $items = collect(range(1, 26))->map(function (int $order): string {
            $alias = 'item_'.str_pad((string) $order, 2, '0', STR_PAD_LEFT);

            return "MAX(CASE WHEN answers.item_order = {$order} THEN answers.raw_score END) as {$alias}";
        })->all();

        yield from DB::table('survey_submissions as submissions')
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
            ->selectRaw(implode(', ', $items))
            ->groupBy(
                'submissions.id', 'profiles.id', 'units.code', 'units.name', 'submissions.instrument_version',
                'submissions.started_at', 'submissions.completed_at', 'submissions.duration_seconds', 'submissions.session_sequence',
            )
            ->orderBy('submissions.id')
            ->cursor();
    }
}
