<?php

namespace App\Models;

use App\Domain\Quality\QualityDecision;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array{fast_completion: bool, identical_answers: bool} $flags
 * @property QualityDecision|null $decision
 * @property string|null $reason
 * @property int|null $reviewed_by
 * @property CarbonInterface|null $reviewed_at
 * @property-read SurveySubmission $submission
 * @property-read User $reviewer
 */
class QualityReview extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'decision' => QualityDecision::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<SurveySubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(SurveySubmission::class, 'survey_submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
