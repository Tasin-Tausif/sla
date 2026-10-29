<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;

class TicketActivityLogger
{
    /**
     * Log a ticket activity.
     *
     * Never place a reply or internal-note body in $description or
     * $properties; only log metadata such as event names and old/new values.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        Ticket $ticket,
        string $event,
        string $description,
        ?User $causer = null,
        array $properties = [],
    ): void {
        activity('ticket')
            ->performedOn($ticket)
            ->causedBy($causer)
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }
}
