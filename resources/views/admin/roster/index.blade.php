@extends('layouts.app')

@section('title', 'Shift Roster')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input type="date" name="week" value="{{ $start->toDateString() }}" class="form-control"></div>
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <x-select name="department_id" :options="$departments" :value="request('department_id')" placeholder="All Departments" class="col-md-3" />
        <div class="col-md-3"><button class="btn btn-secondary w-100">Show Week</button></div>
    </div>
</form>

<x-page-header :title="$start->format('d M').' - '.$end->format('d M Y')" subtitle="Roster shifts override the employee's regular shift for those days.">
    <a href="?{{ http_build_query(request()->except('week') + ['week' => $start->copy()->subWeek()->toDateString()]) }}" class="btn btn-outline-secondary"><i class="hgi-stroke hgi-arrow-left-01"></i></a>
    <a href="?{{ http_build_query(request()->except('week') + ['week' => $start->copy()->addWeek()->toDateString()]) }}" class="btn btn-outline-secondary"><i class="hgi-stroke hgi-arrow-right-01"></i></a>
    <a href="{{ route('admin.roster.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Assign Shifts</a>
</x-page-header>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead>
            <tr>
                <th>Employee</th>
                @for ($day = $start->copy(); $day->lte($end); $day->addDay())
                    <th class="text-center {{ $day->isToday() ? 'table-primary' : '' }}">{{ $day->format('D') }}<div class="small fw-normal">{{ $day->format('d M') }}</div></th>
                @endfor
            </tr>
            </thead>
            <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td class="text-nowrap">{{ $employee->name }}</td>
                    @foreach ($grid[$employee->id] as $date => $shift)
                        <td class="text-center small {{ $shift && $employee->shift_id !== $shift->id ? 'table-warning' : '' }}">
                            {{ $shift ? $shift->name : 'Default' }}
                            @if ($shift)<div class="text-muted">{{ substr($shift->start_time, 0, 5) }}-{{ substr($shift->end_time, 0, 5) }}</div>@endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No employees.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Current and upcoming assignments</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Employee</th><th>Shift</th><th>From</th><th>To</th><th>Note</th><th></th></tr></thead>
            <tbody>
            @forelse ($assignments as $assignment)
                <tr>
                    <td>{{ $assignment->employee->name }}</td>
                    <td>{{ $assignment->shift->label() }}</td>
                    <td>{{ $assignment->start_date->format('d M Y') }}</td>
                    <td>{{ $assignment->end_date->format('d M Y') }}</td>
                    <td class="small">{{ $assignment->note }}</td>
                    <td class="text-end"><x-delete-button :action="route('admin.roster.destroy', $assignment)" message="Remove this assignment? Attendance for past days will be recalculated." /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No roster assignments. Employees work their regular shift.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
