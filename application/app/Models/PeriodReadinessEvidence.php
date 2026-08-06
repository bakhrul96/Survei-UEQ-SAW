<?php

namespace App\Models;

use App\Domain\Study\ReadinessEvidenceKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodReadinessEvidence extends Model
{
    protected $table = 'period_readiness_evidence';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'kind' => ReadinessEvidenceKind::class,
            'verified_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<EvaluationPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
