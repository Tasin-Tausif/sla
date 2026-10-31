<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-content leading-tight">
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
        <div class="page-shell space-y-6">
            @if (auth()->user()->hasRole('agent'))
                <p class="text-sm text-content-muted">{{ __('Showing tickets you can access') }}</p>
            @endif
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($stats as $name => $count)
                    <x-button href="{{ route('tickets.index', $statLinks[$name]) }}" variant="secondary" class="rounded-lg bg-surface p-5 normal-case shadow-sm border-0">
                        <div class="text-sm capitalize text-content-muted">{{ $name }}</div>
                        <div class="mt-2 text-3xl font-semibold text-content">{{ $count }}</div>
                    </x-button>
                @endforeach
            </div>
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg bg-surface p-6 shadow-sm">
                    <h3 class="font-semibold text-content">{{ __('By priority') }}</h3>
                    @php $priorityMax = max($byPriority ?: [1]); @endphp
                    @foreach ($byPriority as $priority => $total)
                        <div class="mt-3">
                            <div class="flex justify-between text-sm"><span>{{ \App\Enums\TicketPriority::from($priority)->label() }}</span><span>{{ $total }}</span></div>
                            <div class="mt-1 h-2 rounded bg-bg"><div class="h-2 rounded bg-primary-soft" style="width: {{ $priorityMax ? ($total / $priorityMax) * 100 : 0 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                @if (auth()->user()->hasRole('admin'))
                    <div class="rounded-lg bg-surface p-6 shadow-sm">
                        <h3 class="font-semibold text-content">{{ __('By agent') }}</h3>
                        @php $agentMax = max($byAgent ?: [1]); @endphp
                        @foreach ($byAgent as $agent => $total)
                            <div class="mt-3">
                                <div class="flex justify-between text-sm"><span>{{ $agent }}</span><span>{{ $total }}</span></div>
                                <div class="mt-1 h-2 rounded bg-bg"><div class="h-2 rounded bg-success" style="width: {{ $agentMax ? ($total / $agentMax) * 100 : 0 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="rounded-lg bg-surface p-6 shadow-sm">
                <h3 class="font-semibold text-content">{{ __('Urgent tickets') }}</h3>
                <div class="mt-4 divide-y divide-line">
                    @forelse ($urgentTickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" class="flex items-center justify-between gap-4 py-3 hover:bg-surface-muted">
                            <div><div class="font-medium text-primary">{{ $ticket->ticket_number }}</div><div class="text-sm text-content-muted">{{ $ticket->subject }}</div></div>
                            <x-ticket.sla-badge :ticket="$ticket" />
                        </a>
                    @empty
                        <p class="py-3 text-sm text-content-muted">{{ __('No active tickets.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
