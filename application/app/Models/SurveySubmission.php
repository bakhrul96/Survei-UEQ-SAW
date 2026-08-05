<?php

namespace App\Models;

use Database\Factories\SurveySubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SurveySubmission extends Model
{
    /** @use HasFactory<SurveySubmissionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'session_sequence' => 'integer',
        ];
    }

    /** @return BelongsTo<EvaluationPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    /** @return BelongsTo<AnonymousRespondent, $this> */
    public function respondent(): BelongsTo
    {
        return $this->belongsTo(AnonymousRespondent::class, 'anonymous_respondent_id');
    }

    /** @return BelongsTo<SurveySession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(SurveySession::class, 'survey_session_id');
    }

    /** @return BelongsTo<EvaluationUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class, 'evaluation_unit_id');
    }

    /** @return HasMany<SurveyAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /** @return HasOne<QualityReview, $this> */
    public function qualityReview(): HasOne
    {
        return $this->hasOne(QualityReview::class);
    }
}
