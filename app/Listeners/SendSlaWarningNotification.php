<?php

namespace App\Listeners;

use App\Events\SlaWarningTriggered;
use App\Notifications\SlaWarningNotification;
use App\Support\TicketRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendSlaWarningNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(SlaWarningTriggered $event): void
    {
        Notification::send(
            TicketRecipients::forEscalation($event->ticket),
            new SlaWarningNotification($event->ticket),
        );
    }
}
