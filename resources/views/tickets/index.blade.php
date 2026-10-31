<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-content leading-tight">
                {{ __('Tickets') }}
            </h2>

            @can('create', App\Models\Ticket::class)
                <x-button href="{{ route('tickets.create') }}">
                    {{ __('New Ticket') }}
                </x-button>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="page-shell space-y-4">
            <div class="flex items-center space-x-4">
                <x-button href="{{ route('tickets.index', ['view' => 'active']) }}" :variant="$view === 'active' ? 'primary' : 'secondary'" class="px-4 py-2 rounded-md text-sm font-medium">
                    {{ __('Active') }}
                </x-button>
                <x-button href="{{ route('tickets.index', ['view' => 'closed']) }}" :variant="$view === 'closed' ? 'primary' : 'secondary'" class="px-4 py-2 rounded-md text-sm font-medium">
                    {{ __('Closed') }}
                </x-button>
            </div>

            <form method="GET" action="{{ route('tickets.index') }}" class="bg-surface p-4 shadow-sm sm:rounded-lg">
                <input type="hidden" name="view" value="{{ $view }}">
                @php
                    $filterNames = ['q', 'status', 'priority', 'category_id', 'sla_status', 'date_from', 'date_to', 'assigned_agent_id', 'customer'];
                    $hasActiveFilters = request()->hasAny($filterNames);
                    $activeFilterCount = collect($filterNames)->filter(fn (string $name): bool => request()->filled($name))->count();
                @endphp
                <input type="checkbox" id="filters-toggle" class="peer sr-only" @checked($hasActiveFilters)>
                <label for="filters-toggle" class="mb-4 inline-flex min-h-[var(--control-height)] cursor-pointer items-center gap-2 rounded-md border border-line px-3 py-2 text-sm font-medium text-content focus:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-focus-ring md:hidden">
                    {{ __('Filters') }}
                    @if ($activeFilterCount > 0)
                        <span class="rounded-full bg-primary-soft px-2 py-0.5 text-xs text-primary">{{ $activeFilterCount }}</span>
                    @endif
                </label>
                <div class="hidden grid-cols-1 gap-4 peer-checked:grid md:grid md:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full"
                                      :value="request('q')" placeholder="Ticket number, subject or description" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <x-form.select name="status" :options="\App\Enums\TicketStatus::options()" placeholder="All statuses" :selected="request('status')" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="priority" :value="__('Priority')" />
                        <x-form.select name="priority" :options="\App\Enums\TicketPriority::options()" placeholder="All priorities" :selected="request('priority')" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="category_id" :value="__('Category')" />
                        <x-form.select name="category_id" :options="$categories->pluck('name', 'id')->all()" placeholder="All categories" :selected="request('category_id')" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="sla_status" :value="__('SLA status')" />
                        <x-form.select name="sla_status" :options="['within' => 'Within SLA', 'approaching' => 'Approaching', 'breached' => 'Breached']" placeholder="All SLA statuses" :selected="request('sla_status')" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="date_from" :value="__('Created from')" />
                        <x-text-input id="date_from" name="date_from" type="date" class="mt-1 block w-full" :value="request('date_from')" />
                    </div>
                    <div>
                        <x-input-label for="date_to" :value="__('Created to')" />
                        <x-text-input id="date_to" name="date_to" type="date" class="mt-1 block w-full" :value="request('date_to')" />
                    </div>
                    @if ($isStaff)
                        <div>
                            <x-input-label for="assigned_agent_id" :value="__('Assigned agent')" />
                            <x-form.select name="assigned_agent_id" :options="['unassigned' => 'Unassigned'] + $agents->pluck('name', 'id')->all()" placeholder="Any assignee" :selected="request('assigned_agent_id')" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="customer" :value="__('Customer')" />
                            <x-text-input id="customer" name="customer" type="text" class="mt-1 block w-full" :value="request('customer')" />
                        </div>
                    @endif
                    <div class="col-span-full mt-0 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <x-primary-button class="w-full justify-center sm:w-auto">{{ __('Filter') }}</x-primary-button>
                        <x-button href="{{ route('tickets.index', ['view' => $view]) }}" variant="secondary" size="sm" class="w-full sm:w-auto">{{ __('Reset') }}</x-button>
                    </div>
                </div>
            </form>

            <div class="bg-surface overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-line px-6 py-3 text-sm text-content-muted">
                    {{ trans_choice(':count result|:count results', $tickets->total(), ['count' => $tickets->total()]) }}
                </div>
                @if ($tickets->isEmpty())
                    <div class="p-6 text-content-muted">
                        {{ $view === 'closed' ? __('No closed tickets found.') : __('No active tickets found.') }}
                    </div>
                @else
                    <x-responsive-table>
                        <table class="min-w-full divide-y divide-line">
                            <thead class="bg-surface-muted">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Ticket') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Priority') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('SLA') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Assignee') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-content-muted uppercase">{{ __('Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-surface-muted">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-primary hover:text-primary font-medium">
                                                {{ $ticket->ticket_number }}
                                            </a>
                                            <div class="text-sm text-content-muted">{{ $ticket->subject }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-content-muted">{{ $ticket->category?->name }}</td>
                                        <td class="px-4 py-3"><x-ticket.status-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3"><x-ticket.priority-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3"><x-ticket.sla-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3 text-sm text-content-muted">
                                            {{ $ticket->assignedAgent?->name ?? __('Unassigned') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-content-muted">{{ $ticket->updated_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-responsive-table>

                    <div class="p-4">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
