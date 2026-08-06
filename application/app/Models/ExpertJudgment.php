<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

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

    protected static function booted(): void
    {
        static::updating(function (self $judgment): void {
            if ($judgment->calculationRun()->where('status', 'official')->exists()) {
                throw new LogicException('Official expert judgments are immutable.');
            }
        });

        static::deleting(function (self $judgment): void {
            if ($judgment->calculationRun()->where('status', 'official')->exists()) {
                throw new LogicException('Official expert judgments are immutable.');
            }
        });
    }

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
