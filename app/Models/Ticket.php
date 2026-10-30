<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * @property Carbon $sla_warning_at Moment the ticket becomes "approaching SLA breach": start + resolution_minutes * warning_threshold_percent / 100, in business minutes.
 *
 * sla_breached_at only records that the breach was already escalated and notified (idempotency marker for the scheduler). It is never used to decide whether a ticket is currently breached.
 */
class Ticket extends Model
{
    use HasFactory, SoftDeletes;

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
        'sla_warning_at',
        'sla_warning_sent_at',
        'sla_breached_at',
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

    protected $casts = [
        'priority' => TicketPriority::class,
        'status' => TicketStatus::class,
        'assigned_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_warning_at' => 'datetime',
        'sla_warning_sent_at' => 'datetime',
        'sla_breached_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'escalated_at' => 'datetime',
        'sla_minutes' => 'integer',
        'escalation_level' => 'integer',
    ];

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

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('agent')) {
            return $query->where(function (Builder $query) use ($user): void {
                $query->whereNull('assigned_agent_id')
                    ->orWhere('assigned_agent_id', $user->id);
            });
        }

        if ($user->hasRole('customer')) {
            return $query->where('created_by', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function visibleReplies(User $user): HasMany
    {
        return $this->replies()->visibleTo($user);
    }

    public function scopeOpenForSla(Builder $query): Builder
    {
        return $query->whereIn('status', TicketStatus::activeValues());
    }

    public function scopeWithinSla(Builder $query): Builder
    {
        return $query->openForSla()
            ->where('sla_warning_at', '>', now());
    }

    public function scopeApproachingSla(Builder $query): Builder
    {
        $now = now();

        return $query->openForSla()
            ->where('sla_warning_at', '<=', $now)
            ->where('sla_due_at', '>', $now);
    }

    public function scopeBreachedSla(Builder $query): Builder
    {
        return $query->openForSla()
            ->where('sla_due_at', '<', now());
    }

    public function scopeSearchKeyword(Builder $query, string $term): Builder
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return $query->whereFullText(['subject', 'description'], $term);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->where(function (Builder $query) use ($escaped) {
            $query->where('subject', 'like', "%{$escaped}%")
                ->orWhere('description', 'like', "%{$escaped}%");
        });
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
        return $this->isActive()
            && $this->sla_due_at !== null
            && $this->sla_due_at->isPast();
    }

    /**
     * The SLA state used by the badges, mirroring scopeWithinSla(),
     * scopeApproachingSla() and scopeBreachedSla().
     *
     * within | approaching | breached | resolved
     */
    public function slaState(): string
    {
        if (! $this->isActive()) {
            return 'resolved';
        }

        if ($this->sla_due_at !== null && $this->sla_due_at->isPast()) {
            return 'breached';
        }

        if ($this->sla_warning_at !== null && $this->sla_warning_at->lte(now())) {
            return 'approaching';
        }

        return 'within';
    }

    /**
     * Reset SLA tracking after a priority change.
     *
     * Rule: when the priority changes and the recomputed sla_due_at is in the
     * future, call this method so a ticket downgraded from Critical to Low can
     * warn and escalate again. If the new sla_due_at is already in the past,
     * do not reset.
     */
    public function resetSlaTracking(): void
    {
        $this->sla_warning_sent_at = null;
        $this->sla_breached_at = null;
        $this->escalated_at = null;
        $this->escalation_level = 0;
    }
}
