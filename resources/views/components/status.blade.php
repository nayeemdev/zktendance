@props(['attendance'])
<span class="badge text-bg-{{ $attendance->statusColor() }} {{ $attendance->status === 'weekend' ? 'border' : '' }}">{{ $attendance->statusLabel() }}</span>
@if ($attendance->is_manual)
    <i class="bi bi-pencil-fill text-muted small" title="Manually edited"></i>
@endif
