<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Activity;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'priority',
        'status',
        'category_id',
        'created_by',
        'assigned_agent_id',
        'assigned_at',
        'sla_policy_id',
        'sla_minutes',
        'sla_due_at',
        'sla_warning_sent_at',
        'sla_breached_at',
        'sla_paused_at',
        'sla_paused_minutes',
        'first_response_at',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
        'escalated_at',
        'escalation_level',
        'external_reference',
        'import_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'status' => TicketStatus::class,
            'assigned_at' => 'datetime',
            'sla_due_at' => 'datetime',
            'sla_warning_sent_at' => 'datetime',
            'sla_breached_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_paused_minutes' => 'integer',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'escalated_at' => 'datetime',
            'sla_minutes' => 'integer',
            'escalation_level' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class);
    }

    public function publicReplies(): HasMany
    {
        return $this->replies()->where('is_internal', false);
    }

    public function internalNotes(): HasMany
    {
        return $this->replies()->where('is_internal', true);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', TicketStatus::activeValues());
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_agent_id');
    }

    public function scopeBreached(Builder $query): Builder
    {
        return $query->whereNotNull('sla_breached_at');
    }

    public function isActive(): bool
    {
        return $this->status instanceof TicketStatus && $this->status->isActive();
    }

    public function isResolved(): bool
    {
        return $this->status === TicketStatus::Resolved;
    }

    public function isClosed(): bool
    {
        return $this->status === TicketStatus::Closed;
    }

    public function isBreached(): bool
    {
        return $this->sla_breached_at !== null;
    }
}
