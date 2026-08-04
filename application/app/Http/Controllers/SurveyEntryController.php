<?php

namespace App\Http\Controllers;

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SurveyEntryController
{
    public function __invoke(Request $request, EvaluationPeriod $period, SurveyTokenService $tokens): RedirectResponse
    {
        abort_unless($period->status === PeriodStatus::Active, 404);

        $plain = (string) $request->cookie(config('survey.cookie_name'));
        $issued = $plain !== '' && $tokens->resolve($plain)
            ? null
            : $tokens->issue();
        $response = redirect()->route('survey.consent', $period);

        return $issued
            ? $response->withCookie(cookie(
                config('survey.cookie_name'),
                $issued->plainToken,
                max(1, now()->diffInMinutes($period->closes_at->copy()->addDays(7), false)),
                '/',
                null,
                app()->environment('production'),
                true,
                false,
                'lax',
            ))
            : $response;
    }
}
