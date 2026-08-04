<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyAnswer extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'item_order' => 'integer',
            'raw_score' => 'integer',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SurveySubmission::class, 'survey_submission_id');
    }
}
