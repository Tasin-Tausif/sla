<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tickets') }}
            </h2>

            @can('create', App\Models\Ticket::class)
                <a href="{{ route('tickets.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('New Ticket') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex items-center space-x-4">
                <a href="{{ route('tickets.index', ['view' => 'active']) }}"
                   class="px-4 py-2 rounded-md text-sm font-medium {{ $view === 'active' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    {{ __('Active') }}
                </a>
                <a href="{{ route('tickets.index', ['view' => 'closed']) }}"
                   class="px-4 py-2 rounded-md text-sm font-medium {{ $view === 'closed' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    {{ __('Closed') }}
                </a>
            </div>

            <form method="GET" action="{{ route('tickets.index') }}" class="bg-white p-4 shadow-sm sm:rounded-lg">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="lg:col-span-2">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" class="mt-1 block w-full"
                                      :value="request('q')" placeholder="Ticket number, subject or description" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All statuses</option>
                            @foreach (\App\Enums\TicketStatus::cases() as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="priority" :value="__('Priority')" />
                        <select id="priority" name="priority" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All priorities</option>
                            @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="category_id" :value="__('Category')" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="sla_status" :value="__('SLA status')" />
                        <select id="sla_status" name="sla_status" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">All SLA statuses</option>
                            @foreach (['within' => 'Within SLA', 'approaching' => 'Approaching', 'breached' => 'Breached'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('sla_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
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
                            <select id="assigned_agent_id" name="assigned_agent_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">Any assignee</option>
                                <option value="unassigned" @selected(request('assigned_agent_id') === 'unassigned')>Unassigned</option>
                                @foreach ($agents as $agent)
                                    <option value="{{ $agent->id }}" @selected((string) request('assigned_agent_id') === (string) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="customer" :value="__('Customer')" />
                            <x-text-input id="customer" name="customer" type="text" class="mt-1 block w-full" :value="request('customer')" />
                        </div>
                    @endif
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('tickets.index', ['view' => $view]) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Reset') }}</a>
                </div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="border-b border-gray-200 px-6 py-3 text-sm text-gray-600">
                    {{ trans_choice(':count result|:count results', $tickets->total(), ['count' => $tickets->total()]) }}
                </div>
                @if ($tickets->isEmpty())
                    <div class="p-6 text-gray-500">
                        {{ $view === 'closed' ? __('No closed tickets found.') : __('No active tickets found.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Ticket') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Category') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Priority') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('SLA') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Assignee') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Updated') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($tickets as $ticket)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                                {{ $ticket->ticket_number }}
                                            </a>
                                            <div class="text-sm text-gray-500">{{ $ticket->subject }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $ticket->category?->name }}</td>
                                        <td class="px-4 py-3"><x-ticket.status-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3"><x-ticket.priority-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3"><x-ticket.sla-badge :ticket="$ticket" /></td>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $ticket->assignedAgent?->name ?? __('Unassigned') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $ticket->updated_at->diffForHumans() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
