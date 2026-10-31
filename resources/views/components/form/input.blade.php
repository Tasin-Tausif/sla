@props(['name', 'type' => 'text', 'value' => null])
@php($fieldName = \App\Support\FormFieldName::toDotNotation($name))
@php($hasError = $errors->has($fieldName) || collect($errors->getMessages())->keys()->contains(fn ($key) => str_starts_with($key, $fieldName.'.')))
<input id="{{ $attributes->get('id', $fieldName) }}" name="{{ $name }}" type="{{ $type }}"
    @if (! in_array($type, ['password', 'file'], true)) value="{{ old($fieldName, $value) }}" @endif
    @if ($hasError) aria-invalid="true" @endif
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($hasError ? 'form-control-error' : '')]) }}>
