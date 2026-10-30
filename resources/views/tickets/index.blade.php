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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
