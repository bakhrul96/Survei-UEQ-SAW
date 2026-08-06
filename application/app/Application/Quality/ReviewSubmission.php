<?php

namespace App\Application\Quality;

use App\Application\Calculation\CalculationInputChangeRecorder;
use App\Domain\Quality\QualityDecision;
use App\Domain\Quality\QualityFlagger;
use App\Models\EvaluationPeriod;
use App\Models\QualityReview;
use App\Models\SurveySubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSubmission
{
    public function __construct(
        private readonly QualityFlagger $flagger,
        private readonly CalculationInputChangeRecorder $inputChangeRecorder,
    ) {}

    public function handle(
        SurveySubmission $submission,
        User $reviewer,
        QualityDecision $decision,
        ?string $reason,
    ): QualityReview {
        $reason = filled($reason) ? trim($reason) : null;

        if ($decision === QualityDecision::Excluded && $reason === null) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan eksklusi wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($submission, $reviewer, $decision, $reason): QualityReview {
            $existing = $submission->qualityReview()->first();
            $oldValues = $existing?->only(['flags', 'decision', 'reason', 'reviewed_by', 'reviewed_at']);
            $attributes = [
                'flags' => $existing === null ? $this->flagger->for($submission) : $existing->flags,
                'decision' => $decision,
                'reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ];

            $review = $existing === null
                ? $submission->qualityReview()->create($attributes)
                : tap($existing)->update($attributes);

            $this->inputChangeRecorder->record(
                EvaluationPeriod::query()->findOrFail($submission->evaluation_period_id),
                $reviewer,
                'quality_review.updated',
                QualityReview::class,
                $review->id,
                $oldValues,
                $review->only(['flags', 'decision', 'reason', 'reviewed_by', 'reviewed_at']),
            );

            return $review;
        });
    }
}
