@props(['name', 'label', 'value' => 1, 'checked' => false])
@php($fieldName = \App\Support\FormFieldName::toDotNotation($name))
<label class="inline-flex items-center">
    <input id="{{ $attributes->get('id', $fieldName) }}" type="checkbox" name="{{ $name }}" value="{{ $value }}"
        @checked(old($fieldName, $checked)) {{ $attributes->except('id')->merge(['class' => 'rounded border-line text-primary shadow-sm focus:ring-focus-ring '.($errors->has($fieldName) ? 'form-control-error' : '')]) }}>
    <span class="ms-2 text-sm text-content-muted">{{ $label }}</span>
</label>
