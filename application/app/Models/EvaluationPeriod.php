<?php

namespace App\Models;

use App\Domain\Study\PeriodStatus;
use Carbon\CarbonInterface;
use Database\Factories\EvaluationPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property PeriodStatus $status
 * @property CarbonInterface|null $opens_at
 * @property CarbonInterface|null $closes_at
 */
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
            'consent_estimated_minutes' => 'integer',
            'identical_answers_flag_enabled' => 'boolean',
            'calculation_input_revision' => 'integer',
        ];
    }

    /** @return HasMany<PeriodReadinessEvidence, $this> */
    public function readinessEvidence(): HasMany
    {
        return $this->hasMany(PeriodReadinessEvidence::class);
    }
}
