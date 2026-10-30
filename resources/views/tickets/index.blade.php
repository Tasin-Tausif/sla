<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="flex items-center space-x-4">
                <x-button href="{{ route('tickets.index', ['view' => 'active']) }}" variant="secondary" class="px-4 py-2 rounded-md text-sm font-medium {{ $view === 'active' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    {{ __('Active') }}
                </x-button>
                <x-button href="{{ route('tickets.index', ['view' => 'closed']) }}" variant="secondary" class="px-4 py-2 rounded-md text-sm font-medium {{ $view === 'closed' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    {{ __('Closed') }}
                </x-button>
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
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <x-button href="{{ route('tickets.index', ['view' => $view]) }}" variant="secondary" size="sm">{{ __('Reset') }}</x-button>
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
