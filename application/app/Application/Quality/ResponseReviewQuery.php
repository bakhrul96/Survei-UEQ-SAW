<?php

namespace App\Application\Quality;

use App\Models\EvaluationPeriod;
use App\Models\SurveySubmission;
use Illuminate\Support\Collection;

class ResponseReviewQuery
{
    /** @return Collection<int, ResponseReviewRow> */
    public function for(EvaluationPeriod $period): Collection
    {
        return SurveySubmission::query()
            ->where('evaluation_period_id', $period->id)
            ->with(['qualityReview.reviewer', 'unit'])
            ->orderBy('evaluation_unit_id')
            ->orderBy('completed_at')
            ->get()
            ->map(fn (SurveySubmission $submission): ResponseReviewRow => new ResponseReviewRow(
                submissionId: $submission->id,
                unitCode: $submission->unit->code,
                unitName: $submission->unit->name,
                durationSeconds: $submission->duration_seconds,
                flags: $submission->qualityReview?->flags,
                decision: $submission->qualityReview?->decision?->value,
                reason: $submission->qualityReview?->reason,
                reviewerName: $submission->qualityReview?->reviewer?->name,
                reviewedAt: $submission->qualityReview?->reviewed_at,
            ));
    }
}
