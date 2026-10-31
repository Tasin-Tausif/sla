@props(['name', 'options' => [], 'selected' => null, 'placeholder' => null, 'multiple' => false])
@php($fieldName = \App\Support\FormFieldName::toDotNotation($name))
@php($current = old($fieldName, $selected))
<select id="{{ $attributes->get('id', $fieldName) }}" name="{{ $multiple ? $name.'[]' : $name }}" @if ($multiple) multiple @endif
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($errors->has($fieldName) ? 'form-control-error' : '')]) }}>
    @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected($multiple ? in_array((string) $value, (array) $current, true) : (string) $current === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
