<?php

namespace App\Models;

use Database\Factories\AnonymousRespondentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnonymousRespondent extends Model
{
    /** @use HasFactory<AnonymousRespondentFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(RespondentProfile::class);
    }
}
