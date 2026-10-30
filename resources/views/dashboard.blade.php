<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @php
        $statLinks = [
            'open' => [],
            'unassigned' => ['assigned_agent_id' => 'unassigned', 'view' => 'active'],
            'critical' => ['priority' => 'critical'],
            'breached' => ['sla_status' => 'breached'],
            'approaching' => ['sla_status' => 'approaching'],
            'within' => ['sla_status' => 'within'],
            'resolved' => ['status' => 'resolved'],
        ];
    @endphp
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (auth()->user()->hasRole('agent'))
                <p class="text-sm text-gray-600">{{ __('Showing tickets you can access') }}</p>
            @endif
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($stats as $name => $count)
                    <a href="{{ route('tickets.index', $statLinks[$name]) }}" class="rounded-lg bg-white p-5 shadow-sm hover:bg-gray-50">
                        <div class="text-sm capitalize text-gray-500">{{ $name }}</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $count }}</div>
                    </a>
                @endforeach
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-semibold text-gray-900">{{ __('By priority') }}</h3>
                    @php $priorityMax = max($byPriority ?: [1]); @endphp
                    @foreach ($byPriority as $priority => $total)
                        <div class="mt-3">
                            <div class="flex justify-between text-sm"><span>{{ \App\Enums\TicketPriority::from($priority)->label() }}</span><span>{{ $total }}</span></div>
                            <div class="mt-1 h-2 rounded bg-gray-100"><div class="h-2 rounded bg-indigo-500" style="width: {{ $priorityMax ? ($total / $priorityMax) * 100 : 0 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                @if (auth()->user()->hasRole('admin'))
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="font-semibold text-gray-900">{{ __('By agent') }}</h3>
                        @php $agentMax = max($byAgent ?: [1]); @endphp
                        @foreach ($byAgent as $agent => $total)
                            <div class="mt-3">
                                <div class="flex justify-between text-sm"><span>{{ $agent }}</span><span>{{ $total }}</span></div>
                                <div class="mt-1 h-2 rounded bg-gray-100"><div class="h-2 rounded bg-emerald-500" style="width: {{ $agentMax ? ($total / $agentMax) * 100 : 0 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">{{ __('Urgent tickets') }}</h3>
                <div class="mt-4 divide-y divide-gray-200">
                    @forelse ($urgentTickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between gap-4 py-3 hover:bg-gray-50">
                            <div><div class="font-medium text-indigo-600">{{ $ticket->ticket_number }}</div><div class="text-sm text-gray-600">{{ $ticket->subject }}</div></div>
                            <x-ticket.sla-badge :ticket="$ticket" />
                        </a>
                    @empty
                        <p class="py-3 text-sm text-gray-600">{{ __('No active tickets.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
