<?php

namespace App\Models;

use App\Services\Sla\SlaConfigLoader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['holiday_date', 'name'];

    protected static function booted(): void
    {
        static::saved(fn () => SlaConfigLoader::forget());
        static::deleted(fn () => SlaConfigLoader::forget());
    }

    protected $casts = [
        'holiday_date' => 'date:Y-m-d',
    ];

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('holiday_date', $year);
    }
}
