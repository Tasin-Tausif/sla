@props(['ticket'])

@php
    $classes = match ($ticket->priority) {
        \App\Enums\TicketPriority::Low => 'bg-gray-100 text-gray-700',
        \App\Enums\TicketPriority::Medium => 'bg-sky-100 text-sky-800',
        \App\Enums\TicketPriority::High => 'bg-orange-100 text-orange-800',
        \App\Enums\TicketPriority::Critical => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $ticket->priority->label() }}
</span>
