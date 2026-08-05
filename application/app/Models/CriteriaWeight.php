<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriteriaWeight extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'c1_points' => 'integer',
            'c2_points' => 'integer',
            'c3_points' => 'integer',
        ];
    }

    /** @return BelongsTo<TechnicalInformant, $this> */
    public function informant(): BelongsTo
    {
        return $this->belongsTo(TechnicalInformant::class, 'technical_informant_id');
    }
}
