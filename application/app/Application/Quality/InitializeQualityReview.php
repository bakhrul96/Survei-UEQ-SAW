<?php

namespace App\Application\Quality;

use App\Domain\Quality\QualityFlagger;
use App\Models\QualityReview;
use App\Models\SurveySubmission;

final class InitializeQualityReview
{
    public function __construct(private readonly QualityFlagger $flagger) {}

    public function handle(SurveySubmission $submission): QualityReview
    {
        return $submission->qualityReview()->updateOrCreate([], [
            'flags' => $this->flagger->for($submission),
            'decision' => null,
            'reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }
}
