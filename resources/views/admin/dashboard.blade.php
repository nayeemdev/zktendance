@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $t = $stats['today'];
    $y = $stats['yesterday'];
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $delta = $t['rate'] !== null && $y['rate'] !== null ? round($t['rate'] - $y['rate'], 1) : null;
    $pending = $stats['pending_leaves'] + $stats['pending_corrections'] + $stats['pending_overtime'];
    $splitTotal = max(1, $t['in'] + $t['leave'] + $t['not_in']);
    $split = [
        ['On time', $t['in'] - $t['late'], 'var(--viz-present)'],
        ['Late', $t['late'], 'var(--viz-late)'],
        ['On leave', $t['leave'], 'var(--viz-leave)'],
        ['Not in yet', $t['not_in'], 'var(--viz-absent)'],
    ];
    $chart = $stats['chart'];
    $currency = setting('currency_symbol', '৳');
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted small"><i class="hgi-stroke hgi-calendar-03 me-1"></i>{{ today()->format('l, d F Y') }}</div>
    @if ($branches->count() > 1)
        <form>
            <select name="branch_id" class="form-select form-select-sm" style="min-width: 190px" onchange="this.form.submit()" aria-label="Branch">
                <option value="">All branches</option>
                @foreach ($branches as $id => $name)
                    <option value="{{ $id }}" @selected(request('branch_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </form>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-5">
        <div class="hero h-100">
            <div class="position-relative" style="z-index: 1">
                <div class="fw-semibold mb-3">{{ $greeting }}, {{ strtok(auth()->user()->name, ' ') }}</div>
                <div class="hero-label">Attendance today</div>
                @if ($t['rate'] !== null)
                    <div class="d-flex align-items-end gap-3 my-2">
                        <div class="hero-figure">{{ rtrim(rtrim(number_format($t['rate'], 1), '0'), '.') }}%</div>
                        @if ($delta !== null)
                            <div class="mb-1 small fw-semibold">
                                <i class="hgi-stroke hgi-{{ $delta >= 0 ? 'arrow-up-right-01' : 'arrow-down-right-01' }}"></i>
                                {{ $delta >= 0 ? '+' : '' }}{{ $delta }} pts vs yesterday
                            </div>
                        @endif
                    </div>
                    <div class="hero-meter mb-2" role="img" aria-label="{{ $t['in'] }} of {{ $t['expected'] }} checked in"><span style="width: {{ min(100, $t['rate']) }}%"></span></div>
                    <div class="small opacity-75">{{ $t['in'] }} of {{ $t['expected'] }} expected staff have checked in</div>
                @elseif ($t['off'] > 0)
                    <div class="hero-figure my-2" style="font-size: 2.2rem">Day off</div>
                    <div class="small opacity-75">Today is a weekend or holiday for everyone.</div>
                @else
                    <div class="hero-figure my-2" style="font-size: 2.2rem">Waiting for punches</div>
                    <div class="small opacity-75">Today's attendance appears after the first sync, usually within 15 minutes.</div>
                @endif
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('admin.attendance.index') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-calendar-check-in-01 me-1"></i>Today's attendance</a>
                    @if (auth()->user()->isStaff())
                        <a href="{{ route('admin.employees.create') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-user-add-01 me-1"></i>Add employee</a>
                        <a href="{{ route('admin.payroll.create') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-money-bag-02 me-1"></i>Run payroll</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="row g-3 h-100">
            @foreach ([
                ['Employees', $stats['employees'], 'user-group', 'indigo', auth()->user()->isStaff() ? route('admin.employees.index') : null, 'Active staff'],
                ['Checked in', $t['in'], 'user-check-01', 'green', route('admin.attendance.index'), $y['in'].' yesterday'],
                ['Late today', $t['late'], 'alarm-clock', 'amber', route('admin.attendance.index', ['status' => 'late']), $y['late'].' yesterday'],
                ['Not in yet', $t['not_in'], 'user-remove-01', 'red', route('admin.attendance.index', ['status' => 'absent']), 'No punch today'],
                ['On leave', $t['leave'], 'beach', 'blue', route('admin.attendance.index', ['status' => 'leave']), 'Approved leave'],
                ['Pending approvals', $pending, 'task-done-01', 'slate', route('admin.leaves.index'), $stats['pending_leaves'].' leave, '.$stats['pending_corrections'].' correction, '.$stats['pending_overtime'].' OT'],
            ] as [$label, $value, $icon, $tone, $link, $sub])
                <div class="col-6 col-md-4">
                    @if ($link)<a href="{{ $link }}" class="card stat-tile text-decoration-none">@else<div class="card stat-tile">@endif
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="stat-label">{{ $label }}</span>
                                <span class="stat-icon tone-{{ $tone }}"><i class="hgi-stroke hgi-{{ $icon }}"></i></span>
                            </div>
                            <div class="stat-value tabular">{{ number_format($value) }}</div>
                            <div class="small text-muted text-truncate" title="{{ $sub }}">{{ $sub }}</div>
                        </div>
                    @if ($link)</a>@else</div>@endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>Attendance, last 14 days</span>
                <div class="legend">
                    <span><i style="background: var(--viz-present)"></i>Present</span>
                    <span><i style="background: var(--viz-late)"></i>Late</span>
                    <span><i style="background: var(--viz-leave)"></i>On leave</span>
                    <span><i style="background: var(--viz-absent)"></i>Absent</span>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-box"><canvas id="attendanceChart" aria-label="Stacked column chart of daily attendance for the last 14 days" role="img"></canvas></div>
                <details class="data-table">
                    <summary>Show data</summary>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm tabular">
                            <thead><tr><th>Date</th><th class="text-end">Present</th><th class="text-end">Late</th><th class="text-end">On leave</th><th class="text-end">Absent</th></tr></thead>
                            <tbody>
                            @foreach ($chart['dates'] as $i => $date)
                                <tr><td>{{ $date }}</td><td class="text-end">{{ $chart['present'][$i] }}</td><td class="text-end">{{ $chart['late'][$i] }}</td><td class="text-end">{{ $chart['leave'][$i] }}</td><td class="text-end">{{ $chart['absent'][$i] }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Today at a glance</div>
            <div class="card-body">
                <div class="split-bar mb-3" role="img" aria-label="Today's attendance split">
                    @foreach ($split as [$label, $value, $color])
                        @if ($value > 0)
                            <span style="width: {{ $value / $splitTotal * 100 }}%; background: {{ $color }}" title="{{ $label }}: {{ $value }}"></span>
                        @endif
                    @endforeach
                </div>
                <ul class="list-unstyled mb-4">
                    @foreach ($split as [$label, $value, $color])
                        <li class="d-flex justify-content-between align-items-center py-1">
                            <span class="d-flex align-items-center gap-2"><i class="status-dot" style="background: {{ $color }}"></i>{{ $label }}</span>
                            <span class="fw-semibold tabular">{{ $value }}</span>
                        </li>
                    @endforeach
                    @if ($t['off'] > 0)
                        <li class="d-flex justify-content-between align-items-center py-1 text-muted">
                            <span class="d-flex align-items-center gap-2"><i class="status-dot offline"></i>Weekend or holiday</span>
                            <span class="fw-semibold tabular">{{ $t['off'] }}</span>
                        </li>
                    @endif
                </ul>

                <div class="card-title-sm mb-2">Devices</div>
                @forelse ($stats['devices'] as $device)
                    <div class="d-flex align-items-center gap-2 py-1">
                        <span class="status-dot {{ $device->last_error ? 'error' : ($device->isOnline() ? 'online' : 'offline') }}"></span>
                        <span class="min-w-0 flex-grow-1">
                            <span class="d-block text-truncate small fw-semibold">{{ $device->name }}</span>
                            <span class="d-block small text-muted">{{ $device->branch->name }} &middot; {{ $device->last_synced_at?->diffForHumans() ?? 'never synced' }}</span>
                        </span>
                        <span class="badge {{ $device->isOnline() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $device->isOnline() ? 'Online' : 'Offline' }}</span>
                    </div>
                @empty
                    <div class="small text-muted">No devices yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header">Checked in by department</div>
            <div class="card-body">
                @forelse ($stats['departments'] as $department)
                    @php($pct = $department['total'] ? round($department['in'] / $department['total'] * 100) : 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold text-truncate">{{ $department['name'] }}</span>
                            <span class="text-muted tabular">{{ $department['in'] }} / {{ $department['total'] }}</span>
                        </div>
                        <div class="meter" role="img" aria-label="{{ $department['name'] }}: {{ $pct }} percent checked in"><span style="width: {{ $pct }}%"></span></div>
                    </div>
                @empty
                    <div class="empty-state"><i class="hgi-stroke hgi-hierarchy-square-02"></i>No departments yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Most late this month</span>
                <a href="{{ route('admin.reports.late') }}" class="small fw-normal">Report</a>
            </div>
            <div class="card-body pt-2">
                @forelse ($stats['late_leaders'] as $row)
                    <div class="rank-row">
                        <span class="avatar avatar-sm">{{ mb_strtoupper(mb_substr($row['employee']->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-grow-1">
                            <span class="d-block text-truncate small fw-semibold">{{ $row['employee']->name }}</span>
                            <span class="d-block small text-muted">{{ minutes_to_hours($row['minutes']) }} hrs late in total</span>
                        </span>
                        <span class="badge text-bg-warning tabular">{{ $row['count'] }}&times;</span>
                    </div>
                @empty
                    <div class="empty-state"><i class="hgi-stroke hgi-checkmark-circle-02"></i>Nobody was late this month.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-12 col-xl-4">
        <div class="card h-100">
            <div class="card-header">Net payroll, last 6 months</div>
            <div class="card-body">
                @if (array_sum($stats['payroll']['values']) > 0)
                    <div class="chart-box sm"><canvas id="payrollChart" role="img" aria-label="Column chart of net payroll for the last 6 months"></canvas></div>
                    <details class="data-table">
                        <summary>Show data</summary>
                        <table class="table table-sm tabular mt-2">
                            @foreach ($stats['payroll']['labels'] as $i => $label)
                                <tr><td>{{ $label }}</td><td class="text-end">{{ money($stats['payroll']['values'][$i]) }}</td></tr>
                            @endforeach
                        </table>
                    </details>
                @else
                    <div class="empty-state"><i class="hgi-stroke hgi-money-bag-02"></i>Approved payroll will show here.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>Latest punches</span>
                @if (auth()->user()->isStaff())<a href="{{ route('admin.punches.index') }}" class="small fw-normal">View all</a>@endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <tbody>
                    @forelse ($recentPunches as $log)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-sm">{{ mb_strtoupper(mb_substr($log->employee?->name ?? '?', 0, 1)) }}</span>
                                    <span>
                                        <span class="d-block small fw-semibold">{{ $log->employee?->name ?? 'Unknown ID '.$log->device_user_id }}</span>
                                        <span class="d-block small text-muted">{{ $log->device?->name ?? 'Manual' }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end small">
                                <span class="fw-semibold">{{ $log->punched_at->format('h:i A') }}</span>
                                <span class="d-block text-muted">{{ $log->punched_at->format('d M') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td><div class="empty-state"><i class="hgi-stroke hgi-fingerprint-scan"></i>No punches yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-header">On leave today</div>
            <div class="card-body pt-2">
                @forelse ($onLeaveToday as $leave)
                    <div class="rank-row">
                        <span class="stat-icon tone-blue" style="width: 32px; height: 32px"><i class="hgi-stroke hgi-beach"></i></span>
                        <span class="min-w-0">
                            <span class="d-block small fw-semibold text-truncate">{{ $leave->employee->name }}</span>
                            <span class="d-block small text-muted">{{ $leave->leaveType->name }} until {{ $leave->end_date->format('d M') }}</span>
                        </span>
                    </div>
                @empty
                    <div class="empty-state"><i class="hgi-stroke hgi-user-group"></i>Everyone is in.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-header">Coming up</div>
            <div class="card-body pt-2">
                @foreach ($stats['holidays'] as $holiday)
                    <div class="rank-row">
                        <span class="stat-icon tone-indigo" style="width: 32px; height: 32px"><i class="hgi-stroke hgi-calendar-03"></i></span>
                        <span class="min-w-0">
                            <span class="d-block small fw-semibold text-truncate">{{ $holiday->name }}</span>
                            <span class="d-block small text-muted">{{ $holiday->date->format('D, d M') }}{{ $holiday->branch ? ' · '.$holiday->branch->name : '' }}</span>
                        </span>
                    </div>
                @endforeach
                @foreach ($stats['birthdays'] as $birthday)
                    <div class="rank-row">
                        <span class="stat-icon tone-amber" style="width: 32px; height: 32px"><i class="hgi-stroke hgi-birthday-cake"></i></span>
                        <span class="min-w-0">
                            <span class="d-block small fw-semibold text-truncate">{{ $birthday['employee']->name }}</span>
                            <span class="d-block small text-muted">Birthday {{ $birthday['days'] === 0 ? 'today' : $birthday['date']->format('D, d M') }}</span>
                        </span>
                    </div>
                @endforeach
                @if ($stats['holidays']->isEmpty() && $stats['birthdays']->isEmpty())
                    <div class="empty-state"><i class="hgi-stroke hgi-calendar-03"></i>No holidays or birthdays in the next weeks.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/charts.js') }}?v=2"></script>
<script>
    Chart.defaults.font.family = getComputedStyle(document.documentElement).getPropertyValue('--zk-font');
    zkCharts.stacked(document.getElementById('attendanceChart'), @json($chart));

    @if (array_sum($stats['payroll']['values']) > 0)
        (function () {
            var symbol = @json($currency);
            zkCharts.columns(document.getElementById('payrollChart'), {
                labels: @json($stats['payroll']['labels']),
                values: @json($stats['payroll']['values']),
                format: function (v) { return symbol + ' ' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }); },
                compact: function (v) { return Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(v); }
            });
        })();
    @endif
</script>
@endpush
