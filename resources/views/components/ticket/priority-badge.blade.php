@props(['ticket'])

@php
    $classes = match ($ticket->priority) {
        \App\Enums\TicketPriority::Low => 'bg-bg text-content',
        \App\Enums\TicketPriority::Medium => 'bg-medium-soft text-medium',
        \App\Enums\TicketPriority::High => 'bg-high-soft text-high',
        \App\Enums\TicketPriority::Critical => 'bg-danger-soft text-danger',
        default => 'bg-bg text-content',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $ticket->priority->label() }}
</span>
