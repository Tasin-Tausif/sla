@props(['type' => 'button'])
<x-button variant="secondary" :type="$type" {{ $attributes }}>{{ $slot }}</x-button>
