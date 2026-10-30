<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTicketCreatedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(TicketCreated $event): void
    {
        $recipients = collect([$event->ticket->creator])->filter();

        Notification::send($recipients, new TicketCreatedNotification($event->ticket));
    }
}
