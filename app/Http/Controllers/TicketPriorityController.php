<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Http\Requests\ChangePriorityRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketPriorityController extends Controller
{
    public function update(
        Ticket $ticket,
        ChangePriorityRequest $request,
        TicketService $tickets,
    ): RedirectResponse {
        Gate::authorize('changePriority', $ticket);

        $tickets->changePriority(
            $ticket,
            TicketPriority::from($request->validated('priority')),
            $request->user(),
        );

        return back()->with('status', 'Ticket priority updated.');
    }
}
