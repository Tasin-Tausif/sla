<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Events\TicketResolved;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaCalculator;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private readonly TicketNumberGenerator $numbers,
        private readonly SlaCalculator $calculator,
        private readonly TicketActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $creator, array $data): Ticket
    {
        $ticket = DB::transaction(function () use ($creator, $data): Ticket {
            $priority = $data['priority'] ?? TicketPriority::Medium;
            $priority = $priority instanceof TicketPriority
                ? $priority
                : TicketPriority::from($priority);

            $sla = $this->calculator->forPriority($priority, now());

            $ticket = Ticket::create([
                ...$data,
                'ticket_number' => $this->numbers->generate(),
                'created_by' => $creator->id,
                'priority' => $priority->value,
                'status' => TicketStatus::Open->value,
                'sla_policy_id' => $sla['policy_id'],
                'sla_minutes' => $sla['minutes'],
                'sla_due_at' => $sla['due_at'],
                'sla_warning_at' => $sla['warning_at'],
            ]);

            $this->logger->log($ticket, 'created', 'Ticket created', $creator);

            return $ticket;
        });

        TicketCreated::dispatch($ticket);

        return $ticket;
    }

    public function changePriority(Ticket $ticket, TicketPriority $new, User $actor): void
    {
        $old = $ticket->priority;

        if ($old === $new) {
            return;
        }

        $sla = $this->calculator->forPriority($new, $ticket->created_at);

        $ticket->priority = $new;
        $ticket->sla_policy_id = $sla['policy_id'];
        $ticket->sla_minutes = $sla['minutes'];
        $ticket->sla_due_at = $sla['due_at'];
        $ticket->sla_warning_at = $sla['warning_at'];

        if ($sla['due_at']->isFuture()) {
            $ticket->resetSlaTracking();
        }

        $ticket->save();

        $this->logger->log(
            $ticket,
            'priority_changed',
            'Priority changed',
            $actor,
            [
                'old' => ['priority' => $old->value],
                'new' => ['priority' => $new->value],
            ],
        );
    }

    public function changeStatus(Ticket $ticket, TicketStatus $new, User $actor): void
    {
        $old = $ticket->status;

        if ($old === $new) {
            return;
        }

        $ticket->status = $new;

        switch ($new) {
            case TicketStatus::Resolved:
                $ticket->resolved_at = now();
                $ticket->resolved_by = $actor->id;
                $event = 'resolved';
                $description = 'Ticket resolved';
                break;

            case TicketStatus::Closed:
                $ticket->closed_at = now();
                $ticket->closed_by = $actor->id;
                $event = 'closed';
                $description = 'Ticket closed';
                break;

            case TicketStatus::Open:
            case TicketStatus::InProgress:
                $ticket->resolved_at = null;
                $ticket->resolved_by = null;
                $ticket->closed_at = null;
                $ticket->closed_by = null;
                $event = 'reopened';
                $description = 'Ticket reopened';
                break;

            default:
                $event = 'status_changed';
                $description = 'Status changed';
                break;
        }

        $ticket->save();

        $this->logger->log(
            $ticket,
            $event,
            $description,
            $actor,
            [
                'old' => ['status' => $old->value],
                'new' => ['status' => $new->value],
            ],
        );

        if ($new === TicketStatus::Resolved) {
            TicketResolved::dispatch($ticket, $actor);
        }
    }
}
