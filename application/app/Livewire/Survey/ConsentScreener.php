<?php

namespace App\Livewire\Survey;

use App\Application\Survey\RecordRespondentEligibility;
use App\Domain\Survey\SurveyContext;
use App\Models\EvaluationPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class ConsentScreener extends Component
{
    public EvaluationPeriod $period;

    public bool $consent = false;

    public ?int $age = null;

    public bool $isIndramayuResident = false;

    public bool $hasUsedWongReang = false;

    public function mount(EvaluationPeriod $period, SurveyContext $context): void
    {
        $this->period = $context->ensureAccepting($period);
    }

    public function submit(
        SurveyContext $context,
        RecordRespondentEligibility $recordEligibility,
    ): Redirector|RedirectResponse {
        $period = EvaluationPeriod::query()->findOrFail($this->period->id);
        $this->period = $context->ensureAccepting($period);

        $validated = $this->validate([
            'consent' => ['accepted'],
            'age' => ['required', 'integer', 'between:17,100'],
            'isIndramayuResident' => ['required', 'boolean'],
            'hasUsedWongReang' => ['required', 'boolean'],
        ]);

        $profile = $recordEligibility->handle(
            period: $this->period,
            respondent: $context->respondent(),
            age: $validated['age'],
            isIndramayuResident: $validated['isIndramayuResident'],
            hasUsedWongReang: $validated['hasUsedWongReang'],
        );

        return $profile->eligible
            ? redirect()->route('survey.units', $this->period)
            : redirect()->route('survey.ineligible', $this->period);
    }

    public function render(): View
    {
        return view('livewire.survey.consent-screener')
            ->layout('layouts.survey', ['title' => 'Persetujuan dan Kelayakan']);
    }
}
