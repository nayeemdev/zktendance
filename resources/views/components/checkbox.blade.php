@props(['name', 'label', 'checked' => false, 'help' => null])
@php($id = $attributes->get('id', $name))
<div class="{{ $attributes->get('class', 'mb-3') }} form-check form-switch">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" class="form-check-input" id="{{ $id }}" name="{{ $name }}" value="1" @checked(old($name, $checked))>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
