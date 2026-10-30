@props(['ticket'])

@php
    $classes = match ($ticket->status) {
        \App\Enums\TicketStatus::Open => 'bg-blue-100 text-blue-800',
        \App\Enums\TicketStatus::InProgress => 'bg-indigo-100 text-indigo-800',
        \App\Enums\TicketStatus::WaitingForCustomer => 'bg-amber-100 text-amber-800',
        \App\Enums\TicketStatus::Resolved => 'bg-emerald-100 text-emerald-800',
        \App\Enums\TicketStatus::Closed => 'bg-gray-200 text-gray-700',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $ticket->status->label() }}
</span>
