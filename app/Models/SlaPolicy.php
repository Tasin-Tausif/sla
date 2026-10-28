<?php

namespace App\Models;

use App\Enums\TicketPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = ['priority', 'resolution_minutes', 'warning_threshold_percent', 'is_active'];

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
