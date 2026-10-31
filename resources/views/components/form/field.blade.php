@props(['name', 'label' => null, 'hint' => null, 'required' => false])
@php($fieldName = \App\Support\FormFieldName::toDotNotation($name))
@php($nestedMessages = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, $fieldName.'.'))->flatten()->all())

<div>
    @if ($label)
        <x-input-label :for="$name" :value="$label" :required="$required" />
    @endif
    {{ $slot }}
    @if ($hint)
        <p class="mt-1 text-xs text-content-muted">{{ $hint }}</p>
    @endif
    <x-input-error class="mt-2" :messages="$errors->get($fieldName)" />
    <x-input-error class="mt-2" :messages="$nestedMessages" />
</div>
