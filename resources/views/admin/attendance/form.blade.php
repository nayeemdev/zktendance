@extends('layouts.app')

@section('title', $attendance->exists ? 'Edit Attendance' : 'Manual Attendance')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        @if ($attendance->exists)
            <p class="mb-3"><strong>{{ $attendance->employee->name }}</strong> on {{ $attendance->date->format('l, d M Y') }}</p>
        @endif
        <form method="POST" action="{{ $attendance->exists ? route('admin.attendance.update', $attendance) : route('admin.attendance.store') }}">
            @csrf
            @if ($attendance->exists) @method('PUT') @endif
            @unless ($attendance->exists)
                <div class="row">
                    <x-select name="employee_id" label="Employee" :options="$employees" :value="request('employee_id')" placeholder="Select" class="col-md-8 mb-3" required />
                    <x-input name="date" type="date" label="Date" :value="$attendance->date?->toDateString()" class="col-md-4 mb-3" required />
                </div>
            @endunless
            <div class="row">
                <x-input name="check_in" type="time" label="Check In" :value="$attendance->check_in?->format('H:i')" class="col-md-6 mb-3" />
                <x-input name="check_out" type="time" label="Check Out" :value="$attendance->check_out?->format('H:i')" class="col-md-6 mb-3" />
            </div>
            <x-select name="status" label="Status" :options="\App\Models\Attendance::STATUSES" :value="$attendance->exists && $attendance->is_manual ? $attendance->status : ''" placeholder="Calculate automatically from times" />
            <x-input name="note" label="Reason" :value="$attendance->is_manual ? $attendance->note : ''" required />
            <button class="btn btn-primary">Save</button>
            <a href="{{ url()->previous() }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
