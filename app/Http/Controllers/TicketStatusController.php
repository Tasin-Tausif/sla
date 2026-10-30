<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\ChangeStatusRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketStatusController extends Controller
{
    public function update(
        Ticket $ticket,
        ChangeStatusRequest $request,
        TicketService $tickets,
    ): RedirectResponse {
        $status = TicketStatus::from($request->validated('status'));
        $current = $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);

        $ability = match (true) {
            $status === TicketStatus::Resolved => 'resolve',
            $status === TicketStatus::Closed => 'close',
            in_array($current, [TicketStatus::Resolved, TicketStatus::Closed], true)
                && in_array($status, [TicketStatus::Open, TicketStatus::InProgress], true) => 'reopen',
            default => 'changeStatus',
        };

        Gate::authorize($ability, $ticket);

        $tickets->changeStatus($ticket, $status, $request->user());

        return back()->with('status', 'Ticket status updated.');
    }
}
