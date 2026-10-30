@props(['type' => 'submit'])
<x-button variant="primary" :type="$type" {{ $attributes }}>{{ $slot }}</x-button>
