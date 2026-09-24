@props(['attendance'])
<span class="badge text-bg-{{ $attendance->statusColor() }} {{ $attendance->status === 'weekend' ? 'border' : '' }}">{{ $attendance->statusLabel() }}</span>
@if ($attendance->is_manual)
    <i class="hgi-stroke hgi-pencil-edit-01 text-muted small" title="Manually edited"></i>
@endif
