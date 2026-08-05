<?php

namespace App\Http\Controllers;

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyTokenService;
use App\Models\EvaluationPeriod;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

class SurveyEntryController
{
    public function __invoke(Request $request, EvaluationPeriod $period, SurveyTokenService $tokens): RedirectResponse
    {
        abort_unless($period->status === PeriodStatus::Active, 404);

        $cookieName = config()->string('survey.cookie_name');
        if ($cookieName === '') {
            throw new LogicException('SURVEY_COOKIE_NAME tidak boleh kosong.');
        }

        $cookie = $request->cookie($cookieName);
        $plain = is_string($cookie) ? $cookie : '';
        $issued = $plain !== '' && $tokens->resolve($plain)
            ? null
            : $tokens->issue();
        $response = redirect()->route('survey.consent', $period);

        $closesAt = $period->closes_at;
        abort_unless($closesAt instanceof CarbonInterface, 404);
        $cookieMinutes = max(1, (int) ceil(now()->diffInMinutes($closesAt->copy()->addDays(7), false)));

        return $issued
            ? $response->withCookie(cookie(
                $cookieName,
                $issued->plainToken,
                $cookieMinutes,
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
