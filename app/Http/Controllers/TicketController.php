<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->with(['category', 'assignedAgent', 'creator'])
            ->latest()
            ->paginate();

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        return view('tickets.create');
    }
}
