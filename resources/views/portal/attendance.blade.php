@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
<x-page-header>
    <form class="d-flex gap-2">
        <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control">
        <button class="btn btn-outline-secondary">Show</button>
    </form>
    <a href="{{ route('portal.corrections.create') }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Request Correction</a>
</x-page-header>

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge text-bg-success fs-6">Present {{ $summary['present'] }}</span>
    <span class="badge text-bg-warning fs-6">Late {{ $summary['late'] }}</span>
    <span class="badge text-bg-danger fs-6">Absent {{ $summary['absent'] }}</span>
    <span class="badge text-bg-primary fs-6">Leave {{ $summary['leave'] }}</span>
    <span class="badge text-bg-dark fs-6">OT {{ minutes_to_hours($summary['ot_workday_minutes'] + $summary['ot_offday_minutes']) }}</span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Worked</th><th>Late</th><th>OT</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($attendances as $row)
                <tr>
                    <td>{{ $row->date->format('d M, D') }}</td>
                    <td>{{ $row->check_in?->format('h:i A') ?? '-' }}</td>
                    <td>{{ $row->check_out?->format('h:i A') ?? '-' }}</td>
                    <td>{{ minutes_to_hours($row->worked_minutes) }}</td>
                    <td>{{ $row->late_minutes ? $row->late_minutes.'m' : '-' }}</td>
                    <td>{{ $row->overtime_minutes ? minutes_to_hours($row->overtime_minutes) : '-' }}</td>
                    <td><x-status :attendance="$row" /></td>
                    <td class="text-end">
                        @if (in_array($row->status, ['absent', 'late', 'half_day']) || ! $row->check_out)
                            <a href="{{ route('portal.corrections.create', ['date' => $row->date->toDateString()]) }}" class="btn btn-sm btn-outline-secondary">Correct</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No attendance for this month.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
