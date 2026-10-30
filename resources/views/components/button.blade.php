@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-25';
    $sizes = [
        'sm' => 'px-3 py-1.5',
        'md' => 'px-4 py-2',
    ];
    $variants = [
        'primary' => 'bg-gray-800 text-white hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:ring-indigo-500',
        'secondary' => 'bg-white border-gray-300 text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-indigo-500',
        'danger' => 'bg-red-600 text-white hover:bg-red-500 active:bg-red-700 focus:ring-red-500',
        'success' => 'bg-green-600 text-white hover:bg-green-500 active:bg-green-700 focus:ring-green-500',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-400 active:bg-amber-600 focus:ring-amber-500',
    ];
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
