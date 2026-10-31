@props(['ticket'])

@php
    $classes = match ($ticket->status) {
        \App\Enums\TicketStatus::Open => 'bg-open-soft text-open',
        \App\Enums\TicketStatus::InProgress => 'bg-primary-soft text-primary',
        \App\Enums\TicketStatus::WaitingForCustomer => 'bg-warning-soft text-warning',
        \App\Enums\TicketStatus::Resolved => 'bg-resolved-soft text-resolved',
        \App\Enums\TicketStatus::Closed => 'bg-surface-muted text-content',
        default => 'bg-bg text-content',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $ticket->status->label() }}
</span>
