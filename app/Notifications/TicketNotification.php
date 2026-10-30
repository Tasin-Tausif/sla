<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Shared payload for every ticket notification.
 *
 * Subclasses declare via(), $tries and $backoff, and provide subject()
 * and message(). The database channel only ever stores the four keys
 * below, so no reply or note body beyond Str::limit() can leak.
 */
abstract class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'message' => $this->message(),
            'url' => route('tickets.show', $this->ticket),
        ];
    }

    protected function mail(): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->message())
            ->action('View ticket', route('tickets.show', $this->ticket));
    }

    /**
     * The SLA deadline rendered in the support timezone (Asia/Dhaka).
     */
    protected function dueAt(): string
    {
        $dueAt = $this->ticket->sla_due_at;

        if ($dueAt === null) {
            return 'n/a';
        }

        return $dueAt
            ->copy()
            ->setTimezone(config('sla.timezone', 'Asia/Dhaka'))
            ->format('d M Y, h:i A');
    }

    abstract public function subject(): string;

    abstract public function message(): string;
}
