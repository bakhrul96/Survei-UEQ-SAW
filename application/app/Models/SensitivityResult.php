<?php

namespace App\Models;

use App\Domain\Sensitivity\SensitivityScenario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function calculationRun(): BelongsTo
    {
        return $this->belongsTo(CalculationRun::class);
    }

    public function evaluationUnit(): BelongsTo
    {
        return $this->belongsTo(EvaluationUnit::class);
    }
}
