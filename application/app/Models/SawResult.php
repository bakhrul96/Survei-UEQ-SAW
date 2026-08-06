<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SawResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'x1_gap' => 'decimal:10',
            'x2_days' => 'decimal:10',
            'x3_urgency' => 'decimal:10',
            'r1' => 'decimal:10',
            'r2' => 'decimal:10',
            'r3' => 'decimal:10',
            'contribution_c1' => 'decimal:10',
            'contribution_c2' => 'decimal:10',
            'contribution_c3' => 'decimal:10',
            'preference_value' => 'decimal:10',
            'rank' => 'integer',
            'is_tied' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('SAW results are immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('SAW results are immutable.');
        });
    }

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
