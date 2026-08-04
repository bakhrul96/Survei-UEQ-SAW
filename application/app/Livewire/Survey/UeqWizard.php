<?php

namespace App\Livewire\Survey;

use App\Application\Survey\StartSurveySession;
use App\Application\Survey\SubmitSurvey;
use App\Application\Survey\SubmitSurveyData;
use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyContext;
use App\Domain\Survey\SurveyDraftKey;
use App\Models\AnonymousRespondent;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationUnit;
use App\Models\RespondentProfile;
use App\Models\SurveySession;
use App\Models\SurveySubmission;
use App\Models\UeqItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

class UeqWizard extends Component
{
    private const STEP_RANGES = [1 => [1, 7], 2 => [8, 14], 3 => [15, 20], 4 => [21, 26]];

    public EvaluationPeriod $period;
    public EvaluationUnit $unit;
    public int $step = 1;
    /** @var array<int, int> */
    public array $answers = [];
    public bool $confirmedExperience = false;
    public string $idempotencyKey;
    public string $startedAt;
    public string $sessionId;
    public int $respondentId;

    public function mount(EvaluationPeriod $period, EvaluationUnit $unit, SurveyContext $context, StartSurveySession $startSession): void
    {
        abort_unless($period->status === PeriodStatus::Active && $unit->is_active, 404);

        $respondent = $context->respondent();
        $this->ensureEligible($period, $respondent);
        abort_if($this->alreadySubmitted($period, $respondent, $unit), 403);

        $this->period = $period;
        $this->unit = $unit;
        $this->respondentId = $respondent->id;
        $this->sessionId = $startSession->handle($period, $respondent)->id;
        $this->idempotencyKey = (string) Str::uuid();
        $this->startedAt = CarbonImmutable::now()->toIso8601String();
    }

    public function next(): void
    {
        $this->validate($this->rulesForRange($this->step));

        if ($this->step < 4) {
            $this->step++;
        }
    }

    public function previous(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit(SubmitSurvey $submitSurvey, SurveyContext $context): RedirectResponse
    {
        $period = EvaluationPeriod::query()->findOrFail($this->period->id);
        abort_unless($period->status === PeriodStatus::Active, 404);
        $respondent = $context->respondent();
        abort_unless($respondent->id === $this->respondentId, 403);
        $this->ensureEligible($period, $respondent);
        abort_unless(SurveySession::query()
            ->whereKey($this->sessionId)
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->exists(), 403);

        $this->period = $period;
        $this->validate($this->rulesForRange(1, 26));
        $submitSurvey->handle(new SubmitSurveyData(
            periodId: $period->id,
            respondentId: $respondent->id,
            sessionId: $this->sessionId,
            unitId: $this->unit->id,
            idempotencyKey: $this->idempotencyKey,
            instrumentVersion: $period->instrument_version,
            startedAt: CarbonImmutable::parse($this->startedAt),
            answers: array_map(static fn (mixed $answer): int => (int) $answer, $this->answers),
        ));

        $this->dispatch('survey-submitted', key: $this->draftKey);

        return redirect()->route('survey.complete', ['period' => $this->period]);
    }

    public function getDraftKeyProperty(): string
    {
        return SurveyDraftKey::for($this->period->id, $this->respondentId, $this->unit->id, $this->period->instrument_version);
    }

    public function render(): View
    {
        [$from, $to] = self::STEP_RANGES[$this->step];
        $items = UeqItem::query()
            ->where('version', $this->period->instrument_version)
            ->whereBetween('order', [$from, $to])
            ->orderBy('order')
            ->get();

        return view('livewire.survey.ueq-wizard', compact('items'))
            ->layout('layouts.app', ['title' => 'Penilaian UEQ']);
    }

    /** @return array<string, array<int, string>> */
    private function rulesForRange(int $fromStep, ?int $toItem = null): array
    {
        if ($toItem === null) {
            [$fromItem, $toItem] = self::STEP_RANGES[$fromStep];
        } else {
            $fromItem = $fromStep;
        }

        $rules = [];
        if ($fromItem === 1) {
            $rules['confirmedExperience'] = ['accepted'];
        }
        foreach (range($fromItem, $toItem) as $itemOrder) {
            $rules['answers.'.$itemOrder] = ['required', 'integer', 'between:1,7'];
        }

        return $rules;
    }

    private function ensureEligible(EvaluationPeriod $period, AnonymousRespondent $respondent): void
    {
        abort_unless(RespondentProfile::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('eligible', true)
            ->exists(), 403);
    }

    private function alreadySubmitted(EvaluationPeriod $period, AnonymousRespondent $respondent, EvaluationUnit $unit): bool
    {
        return SurveySubmission::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->where('evaluation_unit_id', $unit->id)
            ->where('status', 'submitted')
            ->exists();
    }
}
