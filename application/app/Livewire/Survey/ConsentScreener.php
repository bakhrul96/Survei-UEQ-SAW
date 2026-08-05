<?php

namespace App\Livewire\Survey;

use App\Domain\Study\PeriodStatus;
use App\Domain\Survey\SurveyContext;
use App\Models\EvaluationPeriod;
use App\Models\RespondentProfile;
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

    public function mount(EvaluationPeriod $period): void
    {
        abort_unless($period->status === PeriodStatus::Active, 404);

        $this->period = $period;
    }

    public function submit(SurveyContext $context): Redirector|RedirectResponse
    {
        $period = EvaluationPeriod::query()->findOrFail($this->period->id);
        abort_unless($period->status === PeriodStatus::Active, 404);
        $this->period = $period;

        $validated = $this->validate([
            'consent' => ['accepted'],
            'age' => ['required', 'integer', 'between:17,100'],
            'isIndramayuResident' => ['required', 'boolean'],
            'hasUsedWongReang' => ['required', 'boolean'],
        ]);

        $respondent = $context->respondent();
        $eligible = $validated['age'] >= $this->period->minimum_age
            && $validated['isIndramayuResident']
            && $validated['hasUsedWongReang'];

        RespondentProfile::query()->updateOrCreate(
            [
                'evaluation_period_id' => $this->period->id,
                'anonymous_respondent_id' => $respondent->id,
            ],
            [
                'consented_at' => now(),
                'age' => $validated['age'],
                'is_indramayu_resident' => $validated['isIndramayuResident'],
                'has_used_wong_reang' => $validated['hasUsedWongReang'],
                'eligible' => $eligible,
                'screened_at' => now(),
            ],
        );

        return $eligible
            ? redirect()->route('survey.units', $this->period)
            : redirect()->route('survey.ineligible', $this->period);
    }

    public function render(): View
    {
        return view('livewire.survey.consent-screener')
            ->layout('layouts.survey', ['title' => 'Persetujuan dan Kelayakan']);
    }
}
