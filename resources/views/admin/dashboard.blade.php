@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<form class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">{{ today()->format('l, d F Y') }}</div>
    <select name="branch_id" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">All Branches</option>
        @foreach ($branches as $id => $name)
            <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
        @endforeach
    </select>
</form>

<div class="row g-3 mb-3">
    @foreach ([
        ['Employees', $stats['employees'], 'people', 'primary', route('admin.employees.index')],
        ['Present Today', $stats['present'], 'person-check', 'success', route('admin.attendance.index')],
        ['Late Today', $stats['late'], 'alarm', 'warning', route('admin.attendance.index', ['status' => 'late'])],
        ['Absent Today', $stats['absent'], 'person-x', 'danger', route('admin.attendance.index', ['status' => 'absent'])],
        ['On Leave', $stats['on_leave'], 'calendar-x', 'info', route('admin.attendance.index', ['status' => 'leave'])],
        ['Pending Approvals', $stats['pending_leaves'] + $stats['pending_corrections'], 'hourglass', 'secondary', route('admin.leaves.index')],
    ] as [$label, $value, $icon, $color, $link])
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ $link }}" class="card stat-card text-decoration-none h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi bi-{{ $icon }}"></i></div>
                    <div>
                        <div class="fs-4 fw-semibold text-body">{{ $value }}</div>
                        <div class="small text-muted">{{ $label }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Attendance, last 14 days</div>
            <div class="card-body"><canvas id="attendanceChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                Devices
                <a href="{{ route('admin.devices.index') }}" class="small">Manage</a>
            </div>
            <ul class="list-group list-group-flush">
                @forelse ($stats['devices'] as $device)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $device->name }}</div>
                            <div class="small text-muted">{{ $device->branch->name }} &middot; {{ ucfirst($device->connection_mode) }}</div>
                        </div>
                        <div class="text-end small">
                            <span class="badge text-bg-{{ $device->isOnline() ? 'success' : 'secondary' }}">{{ $device->isOnline() ? 'Online' : 'Offline' }}</span>
                            <div class="text-muted">{{ $device->last_synced_at?->diffForHumans() ?? 'Never synced' }}</div>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No devices yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Latest Punches</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                    @forelse ($recentPunches as $log)
                        <tr>
                            <td>{{ $log->employee?->name ?? 'Unknown ID '.$log->device_user_id }}</td>
                            <td class="text-muted small">{{ $log->device?->name }}</td>
                            <td class="text-end">{{ $log->punched_at->format('d M, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted p-3">No punches yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">On Leave Today</div>
            <ul class="list-group list-group-flush">
                @forelse ($onLeaveToday as $leave)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $leave->employee->name }}</span>
                        <span class="text-muted small">{{ $leave->leaveType->name }} until {{ $leave->end_date->format('d M') }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nobody is on leave today.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    const chart = @json($stats['chart']);
    new Chart(document.getElementById('attendanceChart'), {
        type: 'bar',
        data: {
            labels: chart.labels,
            datasets: [
                { label: 'Present', data: chart.present, backgroundColor: '#198754' },
                { label: 'Late', data: chart.late, backgroundColor: '#ffc107' },
                { label: 'Absent', data: chart.absent, backgroundColor: '#dc3545' },
            ],
        },
        options: { responsive: true, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } },
    });
</script>
@endpush
