<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketReply;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketReplied implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Ticket $ticket, public TicketReply $reply) {}
}
