<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['holiday_date', 'name'];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date:Y-m-d',
        ];
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('holiday_date', $year);
    }
}
