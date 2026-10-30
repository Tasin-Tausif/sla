@props(['type' => 'submit'])
<x-button variant="danger" :type="$type" {{ $attributes }}>{{ $slot }}</x-button>
