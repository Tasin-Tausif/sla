@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block min-h-[var(--control-height)] w-full ps-3 pe-4 py-2 border-l-4 border-primary text-start text-base font-medium text-primary bg-primary-soft focus:outline-none focus:text-primary focus:bg-primary-soft focus:border-primary-hover transition duration-150 ease-in-out'
            : 'block min-h-[var(--control-height)] w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-content-muted hover:text-content hover:bg-surface-muted hover:border-line focus:outline-none focus:text-content focus:bg-surface-muted focus:border-line transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
