<?php

namespace App\Models;

use Database\Factories\SurveySubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(AnonymousRespondent::class, 'anonymous_respondent_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SurveySession::class, 'survey_session_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class, 'evaluation_unit_id');
    }
}
