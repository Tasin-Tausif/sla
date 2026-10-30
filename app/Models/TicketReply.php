<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketReply extends Model
{
    protected $touches = ['ticket'];

    protected $fillable = ['ticket_id', 'user_id', 'body', 'is_internal'];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'reply_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('tickets.internal-note')) {
            return $query;
        }

        return $query->where('is_internal', false);
    }

    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }
}
