@props(['name', 'rows' => 4, 'value' => null])
@php($fieldName = \App\Support\FormFieldName::toDotNotation($name))
<textarea id="{{ $attributes->get('id', $fieldName) }}" name="{{ $name }}" rows="{{ $rows }}"
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($errors->has($fieldName) ? 'form-control-error' : '')]) }}>{{ old($fieldName, $value) }}</textarea>
