@props(['name', 'label', 'value' => 1, 'checked' => false])
<label class="inline-flex items-center">
    <input id="{{ $attributes->get('id', $name) }}" type="checkbox" name="{{ $name }}" value="{{ $value }}"
        @checked(old($name, $checked)) {{ $attributes->except('id')->merge(['class' => 'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500']) }}>
    <span class="ms-2 text-sm text-gray-600">{{ $label }}</span>
</label>
