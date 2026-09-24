@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3])
<div class="{{ $attributes->get('class', 'mb-3') }}">
    @if ($label)
        <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" class="form-control @error($name) is-invalid @enderror" @required($required)>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
