<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SlaWarningNotification extends TicketNotification implements ShouldQueue
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
        return "SLA warning: {$this->ticket->ticket_number}";
    }

    public function message(): string
    {
        return sprintf(
            'SLA warning for %s: priority %s, due by %s.',
            $this->ticket->ticket_number,
            $this->ticket->priority->label(),
            $this->dueAt(),
        );
    }
}
