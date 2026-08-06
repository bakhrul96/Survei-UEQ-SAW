<?php

namespace App\Application\Reporting;

use App\Models\EvaluationPeriod;
use Illuminate\Support\Facades\DB;

class ReleaseOneDashboardQuery
{
    public function for(EvaluationPeriod $period): ReleaseOneDashboardData
    {
        $respondents = DB::table('respondent_profiles')
            ->where('evaluation_period_id', $period->id)
            ->selectRaw('COUNT(*) as unique_respondents, SUM(CASE WHEN eligible = 1 THEN 1 ELSE 0 END) as eligible_respondents')
            ->first();

        $validSubmissions = DB::table('survey_submissions')
            ->select('evaluation_unit_id', DB::raw('COUNT(*) as valid'))
            ->where('evaluation_period_id', $period->id)
            ->where('status', 'submitted')
            ->groupBy('evaluation_unit_id');

        $units = DB::table('evaluation_units as units')
            ->leftJoinSub($validSubmissions, 'submissions', function ($join) {
                $join->on('submissions.evaluation_unit_id', '=', 'units.id');
            })
            ->select('units.code', 'units.name', DB::raw('COALESCE(submissions.valid, 0) as valid'))
            ->orderBy('units.display_order')
            ->get()
            ->map(function (object $unit) use ($period): UnitProgressData {
                $valid = (int) $unit->valid;
                $status = $valid >= $period->target_per_unit
                    ? 'target_reached'
                    : ($valid >= $period->minimum_per_unit ? 'minimal_reached' : 'below_minimum');

                return new UnitProgressData(
                    code: $unit->code,
                    name: $unit->name,
                    valid: $valid,
                    minimum: $period->minimum_per_unit,
                    target: $period->target_per_unit,
                    status: $status,
                );
            });

        $qualityCounts = DB::table('quality_reviews')
            ->join('survey_submissions', 'quality_reviews.survey_submission_id', '=', 'survey_submissions.id')
            ->where('survey_submissions.evaluation_period_id', $period->id)
            ->where('survey_submissions.status', 'submitted')
            ->selectRaw(implode(', ', [
                "SUM(CASE WHEN JSON_EXTRACT(quality_reviews.flags, '$.fast_completion') = true OR JSON_EXTRACT(quality_reviews.flags, '$.identical_answers') = true THEN 1 ELSE 0 END) as flagged",
                "SUM(CASE WHEN quality_reviews.decision = 'excluded' THEN 1 ELSE 0 END) as excluded",
                'COUNT(*) as reviewed',
            ]))
            ->first();

        $totalSubmitted = (int) $units->sum('valid');
        $reviewed = (int) ($qualityCounts->reviewed ?? 0);

        return new ReleaseOneDashboardData(
            uniqueRespondents: (int) $respondents->unique_respondents,
            totalEvaluations: $totalSubmitted,
            eligibleRespondents: (int) $respondents->eligible_respondents,
            flaggedEvaluations: (int) ($qualityCounts->flagged ?? 0),
            excludedEvaluations: (int) ($qualityCounts->excluded ?? 0),
            pendingReviewEvaluations: $totalSubmitted - $reviewed,
            units: $units,
        );
    }
}
