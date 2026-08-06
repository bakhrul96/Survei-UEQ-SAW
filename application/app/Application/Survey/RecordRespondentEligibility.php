<?php

namespace App\Application\Survey;

use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
use Illuminate\Support\Facades\DB;

final class RecordRespondentEligibility
{
    public function handle(
        EvaluationPeriod $period,
        AnonymousRespondent $respondent,
        int $age,
        bool $isIndramayuResident,
        bool $hasUsedWongReang,
    ): RespondentProfile {
        return DB::transaction(function () use ($period, $respondent, $age, $isIndramayuResident, $hasUsedWongReang): RespondentProfile {
            AnonymousRespondent::query()->lockForUpdate()->findOrFail($respondent->id);

            $existing = RespondentProfile::query()
                ->where('evaluation_period_id', $period->id)
                ->where('anonymous_respondent_id', $respondent->id)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return RespondentProfile::query()->create([
                'evaluation_period_id' => $period->id,
                'anonymous_respondent_id' => $respondent->id,
                'consented_at' => now(),
                'age' => $age,
                'is_indramayu_resident' => $isIndramayuResident,
                'has_used_wong_reang' => $hasUsedWongReang,
                'eligible' => $age >= $period->minimum_age && $isIndramayuResident && $hasUsedWongReang,
                'screened_at' => now(),
            ]);
        }, attempts: 3);
    }
}
