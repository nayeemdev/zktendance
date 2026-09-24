@extends('layouts.app')

@section('title', 'Shifts')

@section('content')
<x-page-header subtitle="Employees without a shift use the default shift. Night shifts that end after midnight are supported.">
    <a href="{{ route('admin.shifts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Shift</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Name</th><th>Time</th><th>Grace</th><th>Early Leave Grace</th><th>Half Day Below</th><th>Break</th><th></th></tr></thead>
            <tbody>
            @foreach ($shifts as $shift)
                <tr>
                    <td>{{ $shift->name }} @if($shift->is_default)<span class="badge text-bg-primary">Default</span>@endif</td>
                    <td>{{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }} @if($shift->isOvernight())<span class="badge text-bg-dark">Night</span>@endif</td>
                    <td>{{ $shift->grace_minutes }} min</td>
                    <td>{{ $shift->early_leave_grace_minutes }} min</td>
                    <td>{{ minutes_to_hours($shift->half_day_minutes) }} hrs</td>
                    <td>{{ $shift->break_minutes }} min</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.shifts.edit', $shift) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('admin.shifts.destroy', $shift)" />
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
