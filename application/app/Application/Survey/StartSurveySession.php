<?php

namespace App\Application\Survey;

use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\SurveySession;
use Illuminate\Support\Str;

class StartSurveySession
{
    public function handle(EvaluationPeriod $period, AnonymousRespondent $respondent): SurveySession
    {
        $session = SurveySession::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('last_activity_at', '>=', now()->subMinutes(30))
            ->latest('last_activity_at')
            ->first();

        if ($session !== null) {
            $session->update(['last_activity_at' => now()]);

            return $session;
        }

        return SurveySession::query()->create([
            'id' => (string) Str::uuid(),
            'evaluation_period_id' => $period->id,
            'anonymous_respondent_id' => $respondent->id,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
