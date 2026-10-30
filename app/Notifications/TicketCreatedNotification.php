<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class TicketCreatedNotification extends TicketNotification implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 30];

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mail();
    }

    public function subject(): string
    {
        return "Ticket {$this->ticket->ticket_number} received";
    }

    public function message(): string
    {
        return sprintf(
            'We received your ticket %s. Resolution due by %s.',
            $this->ticket->ticket_number,
            $this->dueAt(),
        );
    }
}
