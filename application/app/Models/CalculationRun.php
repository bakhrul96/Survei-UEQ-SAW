<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property CarbonInterface|null $calculated_at
 * @property CarbonInterface|null $official_locked_at
 * @property CarbonInterface|null $minimum_deviation_approved_at
 */
class CalculationRun extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'input_snapshot' => 'array',
            'warnings' => 'array',
            'included_count' => 'integer',
            'excluded_count' => 'integer',
            'calculated_at' => 'datetime',
            'official_locked_at' => 'datetime',
            'minimum_deviation_approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $run): void {
            $mutableMetadata = [
                'status',
                'locked_by',
                'official_locked_at',
                'minimum_deviation_reason',
                'minimum_deviation_approval_reference',
                'minimum_deviation_approved_by',
                'minimum_deviation_approved_at',
                'updated_at',
            ];
            $immutableAttributes = array_diff(array_keys($run->getDirty()), $mutableMetadata);

            if ($immutableAttributes !== []) {
                throw new LogicException('Calculation run inputs are immutable.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Calculation runs are immutable.');
        });
    }

    /** @return BelongsTo<EvaluationPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /** @return BelongsTo<User, $this> */
    public function minimumDeviationApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'minimum_deviation_approved_by');
    }

    /** @return HasMany<UeqResult, $this> */
    public function ueqResults(): HasMany
    {
        return $this->hasMany(UeqResult::class);
    }

    /** @return HasMany<UeqPooledResult, $this> */
    public function ueqPooledResults(): HasMany
    {
        return $this->hasMany(UeqPooledResult::class);
    }

    /** @return HasMany<SawResult, $this> */
    public function sawResults(): HasMany
    {
        return $this->hasMany(SawResult::class);
    }

    /** @return HasMany<SensitivityResult, $this> */
    public function sensitivityResults(): HasMany
    {
        return $this->hasMany(SensitivityResult::class);
    }

    /** @return HasMany<ExpertJudgment, $this> */
    public function expertJudgments(): HasMany
    {
        return $this->hasMany(ExpertJudgment::class);
    }
}
