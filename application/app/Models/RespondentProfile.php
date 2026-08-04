<?php

namespace App\Models;

use Database\Factories\RespondentProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespondentProfile extends Model
{
    /** @use HasFactory<RespondentProfileFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'screened_at' => 'datetime',
            'is_indramayu_resident' => 'boolean',
            'has_used_wong_reang' => 'boolean',
            'eligible' => 'boolean',
            'age' => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'evaluation_period_id');
    }

    public function respondent(): BelongsTo
    {
        return $this->belongsTo(AnonymousRespondent::class, 'anonymous_respondent_id');
    }
}
