<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertJudgment extends Model
{
    protected $fillable = [
        'calculation_run_id',
        'evaluation_unit_id',
        'operational_order',
        'decision',
        'reason',
        'reviewer_id',
    ];

    protected $casts = [
        'operational_order' => 'integer',
    ];

    /** @return BelongsTo<CalculationRun, $this> */
    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    /** @return BelongsTo<EvaluationUnit, $this> */
    public function evaluationUnit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
