<?php

namespace App\Livewire\Admin;

use App\Application\Quality\ResponseReviewQuery;
use App\Application\Quality\ReviewSubmission;
use App\Domain\Quality\QualityDecision;
use App\Models\EvaluationPeriod;
use App\Models\SurveySubmission;
use Illuminate\View\View;
use Livewire\Component;

class Responses extends Component
{
    public ?int $periodId = null;

    public ?int $submissionId = null;

    public string $decision = 'included';

    public string $reason = '';

    public function mount(): void
    {
        $this->periodId = EvaluationPeriod::query()->first()?->id;
    }

    public function openReview(int $submissionId): void
    {
        $submission = $this->submission($submissionId);
        $review = $submission->qualityReview;

        $this->submissionId = $submission->id;
        $storedDecision = $review?->getRawOriginal('decision');
        $this->decision = is_string($storedDecision) ? $storedDecision : QualityDecision::Included->value;
        $this->reason = $review === null ? '' : ($review->reason ?? '');
        $this->resetValidation();
    }

    public function saveReview(ReviewSubmission $reviews): void
    {
        $validated = $this->validate([
            'submissionId' => ['required', 'integer'],
            'decision' => ['required', 'in:included,excluded'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $reviews->handle(
            $this->submission($validated['submissionId']),
            auth()->user(),
            QualityDecision::from($validated['decision']),
            $validated['reason'],
        );

        $this->reset('submissionId', 'reason');
        $this->decision = QualityDecision::Included->value;
        session()->flash('status', 'Keputusan kualitas respons disimpan.');
    }

    public function render(ResponseReviewQuery $responses): View
    {
        if ($this->periodId === null) {
            return view('livewire.admin.empty-period')
                ->layout('layouts.app', ['title' => 'Review Kualitas Respons']);
        }

        $period = EvaluationPeriod::query()->findOrFail($this->periodId);

        return view('livewire.admin.responses', [
            'period' => $period,
            'responses' => $responses->for($period),
        ])->layout('layouts.app', ['title' => 'Review Kualitas Respons']);
    }

    private function submission(int $submissionId): SurveySubmission
    {
        return SurveySubmission::query()
            ->where('evaluation_period_id', $this->periodId)
            ->with('qualityReview')
            ->findOrFail($submissionId);
    }
}
