@extends('layouts.app')

@section('title', 'Late & Early Leave Report')

@section('content')
@include('admin.reports.filters')

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Date</th><th>Employee</th><th>Department</th><th>Check In</th><th>Check Out</th><th>Late</th><th>Early Leave</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->date->format('d M, D') }}</td>
                    <td>{{ $row->employee->employee_code }} - {{ $row->employee->name }}</td>
                    <td>{{ $row->employee->department?->name }}</td>
                    <td>{{ $row->check_in?->format('h:i A') }}</td>
                    <td>{{ $row->check_out?->format('h:i A') ?? '-' }}</td>
                    <td class="text-danger">{{ $row->late_minutes ? $row->late_minutes.' min' : '' }}</td>
                    <td class="text-warning">{{ $row->early_leave_minutes ? $row->early_leave_minutes.' min' : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">Nobody was late or left early.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
