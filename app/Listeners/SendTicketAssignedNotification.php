<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTicketAssignedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(TicketAssigned $event): void
    {
        if ($event->agent === null) {
            return;
        }

        Notification::send(
            collect([$event->agent]),
            new TicketAssignedNotification($event->ticket),
        );
    }
}
