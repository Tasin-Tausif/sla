@props(['name', 'multiple' => false, 'accept' => null, 'hint' => null])
<input id="{{ $attributes->get('id', $name) }}" name="{{ $multiple ? $name.'[]' : $name }}" type="file"
    @if ($multiple) multiple @endif @if ($accept) accept="{{ $accept }}" @endif
    {{ $attributes->except('id')->merge(['class' => 'form-control text-sm text-gray-600']) }}>
@if ($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
