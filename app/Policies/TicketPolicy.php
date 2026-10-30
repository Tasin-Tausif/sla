<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.view')
            && Ticket::query()
                ->visibleTo($user)
                ->whereKey($ticket->getKey())
                ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if (! $user->hasPermissionTo('tickets.update')) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('agent') && $ticket->assigned_agent_id === $user->id) {
            return true;
        }

        return $user->hasRole('customer')
            && $ticket->created_by === $user->id
            && $this->statusOf($ticket) === TicketStatus::Open;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.reply')
            && $this->view($user, $ticket)
            && $this->statusOf($ticket) !== TicketStatus::Closed;
    }

    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.internal-note')
            && $this->view($user, $ticket);
    }

    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.accept')
            && $user->hasRole('agent')
            && $ticket->assigned_agent_id === null
            && ! in_array($this->statusOf($ticket), [TicketStatus::Resolved, TicketStatus::Closed], true);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.assign')
            && $user->hasRole('admin');
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.resolve')
            && $this->isStaffOwner($user, $ticket);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.resolve')
            && $this->isStaffOwner($user, $ticket);
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.resolve')
            && $this->isStaffOwner($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('customer')) {
            return $ticket->created_by === $user->id
                && $this->statusOf($ticket) === TicketStatus::Resolved;
        }

        return $user->hasPermissionTo('tickets.close')
            && $this->isStaffOwner($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.reopen')
            && $this->isStaffOwner($user, $ticket);
    }

    public function downloadAttachment(User $user, Ticket $ticket, ?Attachment $attachment = null): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        if ($attachment === null) {
            return true;
        }

        if ($attachment->ticket_id !== $ticket->id) {
            return false;
        }

        if ($attachment->isInternal()) {
            return $user->hasPermissionTo('tickets.internal-note');
        }

        return true;
    }

    private function isStaffOwner(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('agent')
            && $ticket->assigned_agent_id === $user->id;
    }

    private function statusOf(Ticket $ticket): TicketStatus
    {
        return $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);
    }
}
