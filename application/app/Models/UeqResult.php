<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UeqResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'n' => 'integer',
            'mean' => 'decimal:10',
            'standard_deviation' => 'decimal:10',
            'standard_error' => 'decimal:10',
            'ci95_lower' => 'decimal:10',
            'ci95_upper' => 'decimal:10',
            'cronbach_alpha' => 'decimal:10',
            'reliability_warnings' => 'array',
            'gap' => 'decimal:10',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('UEQ results are immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('UEQ results are immutable.');
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
