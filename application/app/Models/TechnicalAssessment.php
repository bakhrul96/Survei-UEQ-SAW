<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalAssessment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estimated_days' => 'float',
            'architecture_urgency' => 'integer',
        ];
    }

    /** @return BelongsTo<TechnicalInformant, $this> */
    public function informant(): BelongsTo
    {
        return $this->belongsTo(TechnicalInformant::class, 'technical_informant_id');
    }

    /** @return BelongsTo<EvaluationUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class, 'evaluation_unit_id');
    }
}
