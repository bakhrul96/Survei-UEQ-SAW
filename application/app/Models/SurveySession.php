<?php

namespace App\Models;

use Database\Factories\SurveySessionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveySession extends Model
{
    /** @use HasFactory<SurveySessionFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'submitted_count' => 'integer',
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

    /** @return HasMany<SurveySubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(SurveySubmission::class);
    }
}
