@props(['name', 'label', 'value' => 1, 'checked' => false])
<label class="inline-flex items-center">
    <input id="{{ $attributes->get('id', $name) }}" type="checkbox" name="{{ $name }}" value="{{ $value }}"
        @checked(old($name, $checked)) {{ $attributes->except('id')->merge(['class' => 'rounded border-line text-primary shadow-sm focus:ring-focus-ring']) }}>
    <span class="ms-2 text-sm text-content-muted">{{ $label }}</span>
</label>
