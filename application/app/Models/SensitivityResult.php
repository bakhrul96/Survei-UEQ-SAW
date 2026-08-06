<?php

namespace App\Models;

use App\Domain\Sensitivity\SensitivityScenario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class SensitivityResult extends Model
{
    protected $fillable = [
        'calculation_run_id',
        'scenario',
        'evaluation_unit_id',
        'preference_value',
        'rank',
        'delta_rank',
        'is_tied',
    ];

    protected $casts = [
        'scenario' => SensitivityScenario::class,
        'preference_value' => 'float',
        'rank' => 'integer',
        'delta_rank' => 'integer',
        'is_tied' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Sensitivity results are immutable.');
        });

        static::deleting(function (): void {
            throw new LogicException('Sensitivity results are immutable.');
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
}
