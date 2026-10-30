<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

class TicketRepliedNotification extends TicketNotification implements ShouldQueue
{
    public int $tries = 3;

    public array $backoff = [10, 30];

    public function __construct(Ticket $ticket, public TicketReply $reply)
    {
        parent::__construct($ticket);
    }

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
        return "New reply on {$this->ticket->ticket_number}";
    }

    public function message(): string
    {
        return sprintf(
            'New reply on %s: %s',
            $this->ticket->ticket_number,
            Str::limit($this->reply->body, 200),
        );
    }
}
