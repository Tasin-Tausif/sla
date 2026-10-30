<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReplyRequest;
use App\Models\Ticket;
use App\Services\TicketReplyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TicketReplyController extends Controller
{
    public function store(
        Ticket $ticket,
        StoreReplyRequest $request,
        TicketReplyService $replies,
    ): RedirectResponse {
        Gate::authorize('reply', $ticket);

        $isInternal = $request->boolean('is_internal');

        if ($isInternal) {
            Gate::authorize('addInternalNote', $ticket);
        }

        $replies->add(
            $ticket,
            $request->user(),
            $request->validated('body'),
            $isInternal,
            $request->file('attachments', []),
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', $isInternal ? 'Internal note added.' : 'Reply posted.');
    }
}
