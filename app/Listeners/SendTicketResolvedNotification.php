<?php

namespace App\Listeners;

use App\Events\TicketResolved;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTicketResolvedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(TicketResolved $event): void
    {
        $recipients = collect([$event->ticket->creator])->filter();

        Notification::send($recipients, new TicketResolvedNotification($event->ticket));
    }
}
