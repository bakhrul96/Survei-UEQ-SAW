<?php

namespace App\Models;

use Database\Factories\UeqItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UeqItem extends Model
{
    /** @use HasFactory<UeqItemFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }
}
