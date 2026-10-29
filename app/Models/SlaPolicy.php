<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Services\Sla\SlaConfigLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = ['priority', 'resolution_minutes', 'warning_threshold_percent', 'is_active'];

    protected static function booted(): void
    {
        static::saved(fn () => SlaConfigLoader::forget());
        static::deleted(fn () => SlaConfigLoader::forget());
    }

    protected $casts = [
        'priority' => TicketPriority::class,
        'resolution_minutes' => 'integer',
        'warning_threshold_percent' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function warningMinutes(): int
    {
        return (int) floor($this->resolution_minutes * $this->warning_threshold_percent / 100);
    }
}
