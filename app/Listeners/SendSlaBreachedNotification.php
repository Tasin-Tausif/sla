<?php

namespace App\Listeners;

use App\Events\SlaBreached;
use App\Notifications\SlaBreachedNotification;
use App\Support\TicketRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendSlaBreachedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(SlaBreached $event): void
    {
        Notification::send(
            TicketRecipients::forEscalation($event->ticket),
            new SlaBreachedNotification($event->ticket),
        );
    }
}
