@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null])
@php($id = $attributes->get('id', str_replace(['[', ']', '.'], '_', $name)))
<div class="{{ $attributes->get('class', 'mb-3') }}">
    @if ($label)
        <label for="{{ $id }}" class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ old(str_replace(['[', ']'], ['.', ''], $name), $value) }}"
           {{ $attributes->except(['class', 'id'])->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }} @required($required)>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
