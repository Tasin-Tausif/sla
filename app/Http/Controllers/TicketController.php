<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketActivityLogger;
use App\Services\TicketAssignmentService;
use App\Services\TicketReplyService;
use App\Services\TicketService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Ticket::class);

        $view = $request->query('view') === 'closed' ? 'closed' : 'active';

        $query = Ticket::query()
            ->visibleTo($request->user())
            ->with(['category', 'creator', 'assignedAgent']);

        if ($view === 'closed') {
            $query->whereNotIn('status', TicketStatus::activeValues())
                ->orderByDesc('updated_at');
        } else {
            $query->active()
                ->orderBy('sla_due_at')
                ->orderBy('id');
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'view' => $view,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        return view('tickets.create', [
            'categories' => $this->activeCategories(),
        ]);
    }

    public function store(
        StoreTicketRequest $request,
        TicketService $tickets,
        TicketReplyService $replies,
        TicketAssignmentService $assignment,
    ): RedirectResponse {
        Gate::authorize('create', Ticket::class);

        $ticket = $tickets->create(
            $request->user(),
            $request->safe()->only(['subject', 'description', 'priority', 'category_id']),
        );

        $files = $request->file('attachments', []);

        if ($files !== []) {
            $replies->attachToTicket($ticket, $request->user(), $files);
        }

        try {
            $assignment->autoAssign($ticket);
        } catch (Throwable $exception) {
            Log::warning('Automatic ticket assignment failed after ticket creation.', [
                'ticket_id' => $ticket->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket created successfully.');
    }

    public function show(Request $request, Ticket $ticket): View
    {
        Gate::authorize('view', $ticket);

        $user = $request->user();

        $ticket->load(['category', 'creator', 'assignedAgent']);

        $attachments = $ticket->attachments()
            ->whereNull('reply_id')
            ->with('reply')
            ->get();

        $replies = $ticket->visibleReplies($user)
            ->with(['user', 'attachments.reply'])
            ->oldest()
            ->get();

        $activityQuery = $ticket->activities()->with('causer');

        if (! $user->can('tickets.internal-note')) {
            $activityQuery->where('event', '!=', 'note_added');
        }

        $activities = $activityQuery->latest()->limit(50)->get();

        return view('tickets.show', [
            'ticket' => $ticket,
            'replies' => $replies,
            'attachments' => $attachments,
            'activities' => $activities,
            'agents' => Gate::check('assign', $ticket)
                ? User::query()->role('agent')->orderBy('name')->get()
                : new Collection,
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        Gate::authorize('update', $ticket);

        return view('tickets.edit', [
            'ticket' => $ticket->load('category'),
            'categories' => $this->activeCategories(),
        ]);
    }

    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketActivityLogger $logger,
    ): RedirectResponse {
        Gate::authorize('update', $ticket);

        $data = $request->safe()->only(['subject', 'description', 'category_id']);

        $properties = $this->changedProperties($ticket, $data);

        $ticket->fill($data)->save();

        if ($properties['old'] !== []) {
            $logger->log($ticket, 'updated', 'Ticket updated', $request->user(), $properties);
        }

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Ticket updated successfully.');
    }

    /**
     * Old/new values for the changed fields. The description body is never
     * logged in full; only the fact that it changed is recorded.
     *
     * @param  array<string, mixed>  $data
     * @return array{old: array<string, mixed>, new: array<string, mixed>}
     */
    private function changedProperties(Ticket $ticket, array $data): array
    {
        $old = [];
        $new = [];

        if ($ticket->subject !== $data['subject']) {
            $old['subject'] = $ticket->subject;
            $new['subject'] = $data['subject'];
        }

        if ($ticket->description !== $data['description']) {
            $old['description'] = 'changed';
            $new['description'] = 'changed';
        }

        if ((int) $ticket->category_id !== (int) $data['category_id']) {
            $old['category_id'] = $ticket->category_id;
            $new['category_id'] = (int) $data['category_id'];
        }

        return ['old' => $old, 'new' => $new];
    }

    /**
     * @return Collection<int, Category>
     */
    private function activeCategories(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
