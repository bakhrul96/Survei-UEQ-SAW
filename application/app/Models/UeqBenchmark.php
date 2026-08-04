<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UeqBenchmark extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'good_threshold' => 'decimal:4',
            'verified_at' => 'datetime',
        ];
    }
}
