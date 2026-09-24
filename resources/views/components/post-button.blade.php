@props(['action', 'label', 'icon' => null, 'style' => 'outline-primary', 'confirm' => null])
<form method="POST" action="{{ $action }}" class="d-inline" @if($confirm) data-confirm="{{ $confirm }}" @endif>
    @csrf
    {{ $slot }}
    <button class="btn btn-sm btn-{{ $style }}">@if($icon)<i class="hgi-stroke hgi-{{ $icon }}"></i> @endif{{ $label }}</button>
</form>
