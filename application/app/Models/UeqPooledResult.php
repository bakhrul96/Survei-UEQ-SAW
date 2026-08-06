<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    /** @return BelongsTo<CalculationRun, $this> */
    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }
}
