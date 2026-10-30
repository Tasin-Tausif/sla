@props(['name', 'label' => null, 'hint' => null, 'required' => false])
@php($nestedMessages = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, $name.'.'))->flatten()->all())

<div>
    @if ($label)
        <x-input-label :for="$name" :value="$label" :required="$required" />
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get($name)" />
    <x-input-error class="mt-2" :messages="$nestedMessages" />
</div>
