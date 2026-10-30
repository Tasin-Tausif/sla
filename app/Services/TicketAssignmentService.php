<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TicketAssignmentService
{
    public function __construct(private readonly TicketActivityLogger $logger) {}

    public function claim(Ticket $ticket, User $agent, ?User $actor = null): bool
    {
        $oldStatus = $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);

        $newStatus = $oldStatus === TicketStatus::Open
            ? TicketStatus::InProgress
            : $oldStatus;

        $openStatus = TicketStatus::Open->value;
        $inProgressStatus = TicketStatus::InProgress->value;

        $affected = DB::table('tickets')
            ->where('id', $ticket->id)
            ->whereNull('assigned_agent_id')
            ->update([
                'assigned_agent_id' => $agent->id,
                'assigned_at' => now(),
                'status' => DB::raw(
                    "CASE WHEN status = '{$openStatus}' THEN '{$inProgressStatus}' ELSE status END"
                ),
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            return false;
        }

        User::query()->whereKey($agent->id)->update([
            'last_assigned_at' => now(),
            'updated_at' => now(),
        ]);

        $this->logger->log(
            $ticket,
            'assigned',
            'Ticket assigned',
            $actor ?? $agent,
            [
                'old' => [
                    'assigned_agent_id' => null,
                    'status' => $oldStatus->value,
                ],
                'new' => [
                    'assigned_agent_id' => $agent->id,
                    'status' => $newStatus->value,
                ],
            ],
        );

        $ticket->refresh();

        TicketAssigned::dispatch($ticket, $agent);

        return true;
    }

    public function assignManually(Ticket $ticket, User $agent, User $actor): void
    {
        if (! $actor->hasRole('admin')) {
            throw new AuthorizationException('Only administrators can manually assign tickets.');
        }

        if (! $agent->hasRole('agent')) {
            throw new InvalidArgumentException('The target user must have the agent role.');
        }

        DB::transaction(function () use ($ticket, $agent, $actor): void {
            $locked = Ticket::query()
                ->whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldAgentId = $locked->assigned_agent_id;
            $oldStatus = $locked->status instanceof TicketStatus
                ? $locked->status
                : TicketStatus::from($locked->status);

            if ($oldAgentId === $agent->id) {
                return;
            }

            if ($oldAgentId !== null) {
                $this->logger->log(
                    $locked,
                    'unassigned',
                    'Ticket unassigned',
                    $actor,
                    [
                        'old' => ['assigned_agent_id' => $oldAgentId],
                        'new' => ['assigned_agent_id' => null],
                    ],
                );
            }

            $locked->assigned_agent_id = $agent->id;
            $locked->assigned_at = now();

            if ($oldStatus === TicketStatus::Open) {
                $locked->status = TicketStatus::InProgress;
            }

            $locked->save();

            User::query()->whereKey($agent->id)->update([
                'last_assigned_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logger->log(
                $locked,
                'assigned',
                'Ticket assigned',
                $actor,
                [
                    'old' => [
                        'assigned_agent_id' => $oldAgentId,
                        'status' => $oldStatus->value,
                    ],
                    'new' => [
                        'assigned_agent_id' => $agent->id,
                        'status' => $locked->status->value,
                    ],
                ],
            );
        });

        $ticket->refresh();
    }

    public function autoAssign(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket): bool {
            $resolved = TicketStatus::Resolved->value;
            $closed = TicketStatus::Closed->value;

            $agent = User::query()
                ->role('agent')
                ->where('is_available', true)
                ->select('users.*')
                ->selectSub(
                    Ticket::query()
                        ->selectRaw('count(*)')
                        ->whereColumn('tickets.assigned_agent_id', 'users.id')
                        ->whereNotIn('status', [$resolved, $closed]),
                    'open_count',
                )
                ->whereRaw(
                    '(select count(*) from tickets where tickets.assigned_agent_id = users.id and tickets.status not in (?, ?)) < users.max_open_tickets',
                    [$resolved, $closed],
                )
                ->orderBy('open_count')
                ->orderByRaw('users.last_assigned_at IS NULL DESC')
                ->orderBy('users.last_assigned_at')
                ->orderBy('users.id')
                ->lockForUpdate()
                ->first();

            if ($agent === null) {
                Log::warning('No eligible agent available for automatic ticket assignment.', [
                    'ticket_id' => $ticket->id,
                ]);

                return false;
            }

            return $this->claim($ticket, $agent);
        });
    }
}
