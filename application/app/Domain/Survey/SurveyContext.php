<?php

namespace App\Domain\Survey;

use App\Domain\Study\PeriodStatus;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use Illuminate\Http\Request;

class SurveyContext
{
    public function __construct(
        private readonly Request $request,
        private readonly SurveyTokenService $tokens,
    ) {}

    public function period(): EvaluationPeriod
    {
        $period = $this->request->route('period');

        abort_unless(
            $period instanceof EvaluationPeriod && $period->status === PeriodStatus::Active,
            404,
        );

        return $period;
    }

    public function respondent(): AnonymousRespondent
    {
        $plainToken = (string) $this->request->cookie(config('survey.cookie_name'));
        $respondent = $plainToken === '' ? null : $this->tokens->resolve($plainToken);

        abort_unless($respondent instanceof AnonymousRespondent, 403);

        return $respondent;
    }
}
