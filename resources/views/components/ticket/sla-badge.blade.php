@props(['ticket'])

@php
    [$classes, $label] = match ($ticket->slaState()) {
        'within' => ['bg-green-100 text-green-800', __('Within SLA')],
        'approaching' => ['bg-amber-100 text-amber-800', __('Approaching breach')],
        'breached' => ['bg-red-100 text-red-800', __('SLA breached')],
        default => ['bg-gray-100 text-gray-600', __('SLA resolved')],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $label }}
</span>
