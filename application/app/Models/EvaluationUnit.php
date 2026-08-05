<?php

namespace App\Models;

use Database\Factories\EvaluationUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationUnit extends Model
{
    /** @use HasFactory<EvaluationUnitFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @var list<string> */
    public const WONG_REANG_CODES = [
        'ibadah-yu', 'info-yu', 'dumas-yu', 'sekolah-yu', 'sehat-yu', 'pasar-yu', 'adminduk-yu',
        'kerja-yu', 'renbang-yu', 'izin-yu', 'pajak-yu', 'plesir-yu', 'wifi-yu',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @param  Builder<EvaluationUnit>  $query
     * @return Builder<EvaluationUnit>
     */
    public function scopeForWongReang(Builder $query): Builder
    {
        return $query->whereIn('code', self::WONG_REANG_CODES);
    }
}
