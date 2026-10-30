@props(['name', 'options' => [], 'selected' => null, 'placeholder' => null, 'multiple' => false])
@php($current = old($name, $selected))
<select id="{{ $attributes->get('id', $name) }}" name="{{ $multiple ? $name.'[]' : $name }}" @if ($multiple) multiple @endif
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($errors->has($name) ? 'form-control-error' : '')]) }}>
    @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected($multiple ? in_array((string) $value, (array) $current, true) : (string) $current === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
