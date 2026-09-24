@extends('layouts.app')

@section('title', 'Daily Attendance')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-2"><input type="date" name="date" value="{{ $date->toDateString() }}" class="form-control"></div>
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <x-select name="department_id" :options="$departments" :value="request('department_id')" placeholder="All Departments" class="col-md-3" />
        <x-select name="status" :options="\App\Models\Attendance::STATUSES" :value="request('status')" placeholder="Any Status" class="col-md-2" />
        <div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
    </div>
</form>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex flex-wrap gap-2">
        @foreach (\App\Models\Attendance::STATUSES as $key => $label)
            @if ($summary[$key] ?? 0)
                <span class="badge text-bg-{{ \App\Models\Attendance::COLORS[$key] }} {{ $key === 'weekend' ? 'border' : '' }} fs-6">{{ $label }}: {{ $summary[$key] }}</span>
            @endif
        @endforeach
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.attendance.create', ['date' => $date->toDateString()]) }}" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Manual Entry</a>
        <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#processBox"><i class="bi bi-arrow-repeat"></i> Reprocess</button>
    </div>
</div>

<div class="collapse mb-3" id="processBox">
    <form method="POST" action="{{ route('admin.attendance.process') }}" class="card card-body">
        @csrf
        <div class="row g-2 align-items-end">
            <x-input name="from" type="date" label="From" :value="$date->toDateString()" class="col-md-3" required />
            <x-input name="to" type="date" label="To" :value="$date->toDateString()" class="col-md-3" required />
            <div class="col-md-6">
                <button class="btn btn-primary">Rebuild attendance from punches</button>
                <div class="form-text">Manually edited days are not changed.</div>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Branch</th><th>Shift</th><th>In</th><th>Out</th><th>Worked</th><th>Late</th><th>Early</th><th>OT</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($attendances as $row)
                <tr>
                    <td>{{ $row->employee->name }}<div class="small text-muted">{{ $row->employee->employee_code }}</div></td>
                    <td>{{ $row->employee->branch->name }}</td>
                    <td class="small">{{ $row->shift?->name }}</td>
                    <td>{{ $row->check_in?->format('h:i A') ?? '-' }}</td>
                    <td>{{ $row->check_out?->format('h:i A') ?? '-' }}</td>
                    <td>{{ minutes_to_hours($row->worked_minutes) }}</td>
                    <td>{{ $row->late_minutes ? $row->late_minutes.'m' : '-' }}</td>
                    <td>{{ $row->early_leave_minutes ? $row->early_leave_minutes.'m' : '-' }}</td>
                    <td>{{ $row->overtime_minutes ? minutes_to_hours($row->overtime_minutes) : '-' }}@if($row->overtime_status)<div><x-badge :status="$row->overtime_status" /></div>@endif</td>
                    <td><x-status :attendance="$row" /> @if($row->note)<i class="bi bi-info-circle text-muted" title="{{ $row->note }}"></i>@endif</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.attendance.edit', $row) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        @if ($row->is_manual)
                            <x-post-button :action="route('admin.attendance.reset', $row)" label="" icon="arrow-counterclockwise" style="outline-warning" confirm="Discard the manual change and recalculate from punches?" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-muted">No attendance for this date. Punches are processed every 15 minutes, or use Reprocess.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
