<?php

namespace App\Domain\Survey;

use App\Domain\Study\SurveyPeriodGate;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use Illuminate\Http\Request;

class SurveyContext
{
    public function __construct(
        private readonly Request $request,
        private readonly SurveyTokenService $tokens,
        private readonly SurveyPeriodGate $periodGate,
    ) {}

    public function period(): EvaluationPeriod
    {
        $period = $this->request->route('period');

        abort_unless($period instanceof EvaluationPeriod, 404);
        abort_if($this->periodGate->issues($period) !== [], 404);

        return $period;
    }

    public function ensureAccepting(EvaluationPeriod $period): EvaluationPeriod
    {
        abort_if($this->periodGate->issues($period) !== [], 404);

        return $period;
    }

    public function respondent(): AnonymousRespondent
    {
        $cookie = $this->request->cookie(config()->string('survey.cookie_name'));
        $plainToken = is_string($cookie) ? $cookie : '';
        $respondent = $plainToken === '' ? null : $this->tokens->resolve($plainToken);

        abort_unless($respondent instanceof AnonymousRespondent, 403);

        return $respondent;
    }
}
