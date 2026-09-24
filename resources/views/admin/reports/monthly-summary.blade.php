@extends('layouts.app')

@section('title', 'Monthly Attendance Summary')

@section('content')
@include('admin.reports.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Employee</th><th>Department</th><th>Present</th><th>Late</th><th>Absent</th><th>Paid Leave</th><th>Unpaid Leave</th><th>Off Days</th><th>Late Time</th><th>OT Hours</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['employee']->employee_code }} - {{ $row['employee']->name }}</td>
                    <td>{{ $row['employee']->department?->name }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['late'] }}</td>
                    <td class="{{ $row['absent'] ? 'text-danger' : '' }}">{{ $row['absent'] }}</td>
                    <td>{{ $row['leave'] }}</td>
                    <td>{{ $row['unpaid_leave'] }}</td>
                    <td>{{ $row['off'] }}</td>
                    <td>{{ minutes_to_hours($row['late_minutes']) }}</td>
                    <td>{{ minutes_to_hours($row['ot_workday_minutes'] + $row['ot_offday_minutes']) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-muted">No employees.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
