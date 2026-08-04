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

        return new ReleaseOneDashboardData(
            uniqueRespondents: (int) $respondents->unique_respondents,
            totalEvaluations: $units->sum('valid'),
            eligibleRespondents: (int) $respondents->eligible_respondents,
            units: $units,
        );
    }
}
