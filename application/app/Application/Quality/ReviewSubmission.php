<?php

namespace App\Application\Quality;

use App\Domain\Quality\QualityDecision;
use App\Domain\Quality\QualityFlagger;
use App\Models\AuditEvent;
use App\Models\QualityReview;
use App\Models\SurveySubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewSubmission
{
    public function __construct(private readonly QualityFlagger $flagger) {}

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
                'flags' => $this->flagger->for($submission),
                'decision' => $decision,
                'reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ];

            $review = $existing === null
                ? $submission->qualityReview()->create($attributes)
                : tap($existing)->update($attributes);

            AuditEvent::query()->create([
                'action' => 'quality_review.updated',
                'auditable_type' => QualityReview::class,
                'auditable_id' => $review->id,
                'actor_id' => $reviewer->id,
                'old_values' => $oldValues,
                'new_values' => $review->only(['flags', 'decision', 'reason', 'reviewed_by', 'reviewed_at']),
            ]);

            return $review;
        });
    }
}
