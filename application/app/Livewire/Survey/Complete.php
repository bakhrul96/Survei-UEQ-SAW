<?php

namespace App\Livewire\Survey;

use App\Domain\Survey\SurveyContext;
use App\Models\EvaluationPeriod;
use App\Models\SurveySubmission;
use Illuminate\View\View;
use Livewire\Component;

class Complete extends Component
{
    public EvaluationPeriod $period;
    public SurveySubmission $submission;

    public function mount(EvaluationPeriod $period, SurveyContext $context): void
    {
        $respondent = $context->respondent();
        $this->period = $period;
        $this->submission = SurveySubmission::query()
            ->where('evaluation_period_id', $period->id)
            ->where('anonymous_respondent_id', $respondent->id)
            ->latest('completed_at')
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.survey.complete')
            ->layout('layouts.survey', ['title' => 'Penilaian Tersimpan']);
    }
}
