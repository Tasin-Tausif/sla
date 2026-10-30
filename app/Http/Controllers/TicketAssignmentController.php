<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TicketAssignmentController extends Controller
{
    public function claim(
        Ticket $ticket,
        Request $request,
        TicketAssignmentService $assignments,
    ): RedirectResponse {
        Gate::authorize('claim', $ticket);

        if (! $assignments->claim($ticket, $request->user())) {
            return back()->withErrors([
                'assignment' => 'This ticket was just taken by another agent.',
            ]);
        }

        return back()->with('status', 'You are now the assigned agent.');
    }

    public function assign(
        Ticket $ticket,
        AssignTicketRequest $request,
        TicketAssignmentService $assignments,
    ): RedirectResponse {
        Gate::authorize('assign', $ticket);

        $agent = User::query()->findOrFail($request->validated('agent_id'));

        $assignments->assignManually($ticket, $agent, $request->user());

        return back()->with('status', 'Ticket assigned.');
    }
}
