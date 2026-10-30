<?php

namespace App\Listeners;

use App\Events\TicketReplied;
use App\Models\User;
use App\Notifications\TicketRepliedNotification;
use App\Support\TicketRecipients;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendTicketRepliedNotification implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(TicketReplied $event): void
    {
        $reply = $event->reply;

        // Internal notes are never notified to anyone (ERD design note 13).
        if ($reply->is_internal) {
            return;
        }

        $author = $reply->user;
        $authorIsStaff = $author !== null && $author->hasAnyRole(['admin', 'agent']);

        $recipients = $authorIsStaff
            ? collect([$event->ticket->creator])
            : TicketRecipients::forCustomerReply($event->ticket);

        $recipients = $recipients
            ->filter()
            ->reject(fn (User $user): bool => $author !== null && $user->id === $author->id)
            ->values();

        Notification::send($recipients, new TicketRepliedNotification($event->ticket, $reply));
    }
}
