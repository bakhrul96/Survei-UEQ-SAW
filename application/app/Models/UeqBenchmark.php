<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $source
 * @property CarbonInterface|null $verified_at
 */
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
