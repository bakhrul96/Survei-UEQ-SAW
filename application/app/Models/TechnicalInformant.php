<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TechnicalInformant extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<EvaluationPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    /** @return HasMany<TechnicalAssessment, $this> */
    public function assessments(): HasMany
    {
        return $this->hasMany(TechnicalAssessment::class);
    }

    /** @return HasOne<CriteriaWeight, $this> */
    public function criteriaWeight(): HasOne
    {
        return $this->hasOne(CriteriaWeight::class);
    }
}
