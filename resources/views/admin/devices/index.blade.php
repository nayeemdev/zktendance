@extends('layouts.app')

@section('title', 'ZKTeco Devices')

@section('content')
<x-page-header subtitle="Pull devices are read by the server every 5 minutes. Push devices send punches to the server themselves.">
    <a href="{{ route('admin.devices.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Device</a>
</x-page-header>

@if (! empty($unknown))
    <div class="alert alert-warning">
        <strong>Unregistered devices are contacting the server.</strong> Add them with the serial number below and select push mode.
        <ul class="mb-0 mt-2">
            @foreach ($unknown as $row)
                <li><code>{{ $row['serial'] }}</code> from {{ $row['ip'] }}, last seen {{ $row['seen_at'] }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Branch</th><th>Model</th><th>Mode</th><th>Address / Serial</th><th>Punches</th><th>Last Sync</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($devices as $device)
                <tr>
                    <td><a href="{{ route('admin.devices.show', $device) }}">{{ $device->name }}</a></td>
                    <td>{{ $device->branch->name }}</td>
                    <td>{{ $device->model ?? '-' }}</td>
                    <td><span class="badge text-bg-{{ $device->isPush() ? 'info' : 'primary' }}">{{ ucfirst($device->connection_mode) }}</span></td>
                    <td>{{ $device->isPush() ? $device->serial_number : $device->ip_address.':'.$device->port }}</td>
                    <td>{{ number_format($device->logs_count) }}</td>
                    <td>{{ $device->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                    <td>
                        @if (! $device->is_active)
                            <span class="badge text-bg-secondary">Disabled</span>
                        @elseif ($device->last_error)
                            <span class="badge text-bg-danger" title="{{ $device->last_error }}">Error</span>
                        @else
                            <span class="badge text-bg-{{ $device->isOnline() ? 'success' : 'secondary' }}">{{ $device->isOnline() ? 'Online' : 'Offline' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No devices yet. Add your first ZKTeco device.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
