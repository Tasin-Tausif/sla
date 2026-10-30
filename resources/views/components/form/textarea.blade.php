@props(['name', 'rows' => 4, 'value' => null])
<textarea id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" rows="{{ $rows }}"
    {{ $attributes->except('id')->merge(['class' => 'form-control '.($errors->has($name) ? 'form-control-error' : '')]) }}>{{ old($name, $value) }}</textarea>
