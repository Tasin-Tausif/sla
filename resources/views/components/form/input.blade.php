@props(['name', 'type' => 'text', 'value' => null])
@php($hasError = $errors->has($name) || collect($errors->getMessages())->keys()->contains(fn ($key) => str_starts_with($key, $name.'.')))
<input id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" type="{{ $type }}"
    @if (! in_array($type, ['password', 'file'], true)) value="{{ old($name, $value) }}" @endif
    @if ($hasError) aria-invalid="true" @endif
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($hasError ? 'form-control-error' : '')]) }}>
