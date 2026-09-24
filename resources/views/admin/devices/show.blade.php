@extends('layouts.app')

@section('title', $device->name)

@section('content')
<x-page-header :title="$device->name" :subtitle="$device->branch->name.' · '.($device->model ?? 'ZKTeco').' · '.ucfirst($device->connection_mode).' mode'">
    <a href="{{ route('admin.devices.edit', $device) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edit</a>
    <x-delete-button :action="route('admin.devices.destroy', $device)" message="Remove this device? Downloaded punches will be kept." label="Remove" />
</x-page-header>

@if ($device->last_error)
    <div class="alert alert-danger"><strong>Last error:</strong> {{ $device->last_error }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row small mb-0">
                    @if ($device->isPush())
                        <dt class="col-5">Serial</dt><dd class="col-7">{{ $device->serial_number }}</dd>
                        <dt class="col-5">Last IP</dt><dd class="col-7">{{ $device->ip_address ?? '-' }}</dd>
                    @else
                        <dt class="col-5">Address</dt><dd class="col-7">{{ $device->ip_address }}:{{ $device->port }}</dd>
                    @endif
                    <dt class="col-5">Status</dt><dd class="col-7">{{ $device->isOnline() ? 'Online' : 'Offline' }}</dd>
                    <dt class="col-5">Last Seen</dt><dd class="col-7">{{ $device->last_seen_at?->format('d M Y h:i A') ?? 'Never' }}</dd>
                    <dt class="col-5">Last Sync</dt><dd class="col-7">{{ $device->last_synced_at?->format('d M Y h:i A') ?? 'Never' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold">Actions</div>
            <div class="card-body d-grid gap-2">
                @unless ($device->isPush())
                    <x-post-button :action="route('admin.devices.test', $device)" label="Test Connection" icon="plug" />
                    <x-post-button :action="route('admin.devices.sync', $device)" label="Download Punches Now" icon="cloud-download" style="primary" />
                @endunless
                <a href="{{ route('admin.devices.users', $device) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-people"></i> Device Users &amp; Import</a>
                <x-post-button :action="route('admin.devices.push-users', $device)" label="Send Branch Employees to Device" icon="person-up" confirm="Send all active employees of this branch who have a device ID?" />
                <x-post-button :action="route('admin.devices.sync-time', $device)" label="Sync Device Time" icon="clock-history" />
                <x-post-button :action="route('admin.devices.restart', $device)" label="Restart Device" icon="arrow-repeat" style="outline-warning" confirm="Restart the device?" />
                <x-post-button :action="route('admin.devices.clear-logs', $device)" label="Clear Device Logs" icon="eraser" style="outline-danger" confirm="Delete all punches stored on the device? Download them first." />
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                Latest Punches
                <a href="{{ route('admin.punches.index', ['device_id' => $device->id]) }}" class="small">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Time</th><th>Device User ID</th><th>Employee</th><th>Verify</th></tr></thead>
                    <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->punched_at->format('d M Y h:i:s A') }}</td>
                            <td>{{ $log->device_user_id }}</td>
                            <td>{!! $log->employee ? e($log->employee->name) : '<span class="text-danger">Not linked</span>' !!}</td>
                            <td>{{ $log->verify_type }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">No punches yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($device->isPush())
            <div class="card">
                <div class="card-header bg-white fw-semibold">Queued Commands</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>#</th><th>Command</th><th>Status</th><th>Created</th></tr></thead>
                        <tbody>
                        @forelse ($commands as $command)
                            <tr>
                                <td>{{ $command->id }}</td>
                                <td><code class="small">{{ \Illuminate\Support\Str::limit(str_replace("\t", ' ', $command->command), 70) }}</code></td>
                                <td><x-badge :status="$command->status" /></td>
                                <td>{{ $command->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No commands.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
