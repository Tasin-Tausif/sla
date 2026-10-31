@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex min-h-[var(--control-height)] items-center border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25';
    $sizes = [
        'sm' => 'px-3 py-1.5',
        'md' => 'px-4 py-2',
    ];
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-primary-hover focus:bg-primary-hover active:bg-primary focus:ring-focus-ring',
        'secondary' => 'bg-surface border-line text-content shadow-sm hover:bg-surface-muted focus:ring-focus-ring',
        'danger' => 'bg-danger text-on-primary hover:bg-danger-soft hover:text-danger active:bg-danger focus:ring-danger',
        'success' => 'bg-success text-on-primary hover:bg-success-soft hover:text-success active:bg-success focus:ring-success',
        'warning' => 'bg-warning text-content hover:bg-warning-soft active:bg-warning focus:ring-warning',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
