@props(['ticket'])

@php
    [$classes, $label] = match ($ticket->slaState()) {
        'within' => ['bg-success-soft text-success', __('Within SLA')],
        'approaching' => ['bg-warning-soft text-warning', __('Approaching breach')],
        'breached' => ['bg-danger-soft text-danger', __('SLA breached')],
        default => ['bg-bg text-content-muted', __('SLA resolved')],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $label }}
</span>
