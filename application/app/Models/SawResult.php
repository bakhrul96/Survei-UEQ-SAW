<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SawResult extends Model
{
    protected $guarded = [];

    /** @return BelongsTo<CalculationRun, $this> */
    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    /** @return BelongsTo<EvaluationUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class, 'evaluation_unit_id');
    }
}
