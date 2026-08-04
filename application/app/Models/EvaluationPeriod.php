<?php

namespace App\Models;

use App\Domain\Study\PeriodStatus;
use Database\Factories\EvaluationPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationPeriod extends Model
{
    /** @use HasFactory<EvaluationPeriodFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
            'opens_at' => 'datetime',
            'closes_at' => 'datetime',
            'instrument_verified_at' => 'datetime',
            'configuration_locked_at' => 'datetime',
            'minimum_age' => 'integer',
            'minimum_per_unit' => 'integer',
            'target_per_unit' => 'integer',
            'fast_response_seconds' => 'integer',
        ];
    }
}
