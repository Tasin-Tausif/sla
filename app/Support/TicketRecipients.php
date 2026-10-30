<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketRecipients
{
    /**
     * Every user with the admin role.
     *
     * @return Collection<int, User>
     */
    public static function admins(): Collection
    {
        return User::role('admin')->get();
    }

    /**
     * The assigned agent (if any) plus every admin. An unassigned ticket
     * escalates to the admins only (ERD design note 14).
     *
     * @return Collection<int, User>
     */
    public static function forEscalation(Ticket $ticket): Collection
    {
        $recipients = static::admins();

        if ($ticket->assignedAgent !== null) {
            $recipients->prepend($ticket->assignedAgent);
        }

        return $recipients->unique('id')->values();
    }

    /**
     * The assigned agent when the ticket has one, otherwise the admins.
     *
     * @return Collection<int, User>
     */
    public static function forCustomerReply(Ticket $ticket): Collection
    {
        if ($ticket->assignedAgent !== null) {
            return new Collection([$ticket->assignedAgent]);
        }

        return static::admins();
    }
}
