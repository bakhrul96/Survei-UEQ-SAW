<?php

namespace App\Domain\Quality;

use App\Models\SurveySubmission;

class QualityFlagger
{
    /** @return array{fast_completion: bool, identical_answers: bool} */
    public function for(SurveySubmission $submission): array
    {
        $submission->loadMissing(['answers', 'period']);

        $answers = $submission->answers;

        return [
            'fast_completion' => $submission->duration_seconds < $submission->period->fast_response_seconds,
            'identical_answers' => $submission->period->identical_answers_flag_enabled
                && $answers->count() === 26
                && $answers->pluck('raw_score')->unique()->count() === 1,
        ];
    }
}
