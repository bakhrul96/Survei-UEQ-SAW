<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class UeqPooledResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'n' => 'integer',
            'cronbach_alpha' => 'decimal:10',
            'warnings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('UEQ pooled results are immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('UEQ pooled results are immutable.');
        });
    }

    /** @return BelongsTo<CalculationRun, $this> */
    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }
}
