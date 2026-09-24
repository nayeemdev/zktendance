@extends('layouts.app')

@section('title', $shift->exists ? 'Edit Shift' : 'Add Shift')

@section('content')
<div class="card col-lg-8">
    <div class="card-body">
        <form method="POST" action="{{ $shift->exists ? route('admin.shifts.update', $shift) : route('admin.shifts.store') }}">
            @csrf
            @if ($shift->exists) @method('PUT') @endif
            <div class="row">
                <x-input name="name" label="Shift Name" :value="$shift->name" class="col-md-12 mb-3" required />
                <x-input name="start_time" type="time" label="Start Time" :value="substr((string) $shift->start_time, 0, 5)" class="col-md-6 mb-3" required />
                <x-input name="end_time" type="time" label="End Time" :value="substr((string) $shift->end_time, 0, 5)" class="col-md-6 mb-3" required help="If end time is before start time, the shift ends next day." />
                <x-input name="grace_minutes" type="number" label="Late Grace (minutes)" :value="$shift->grace_minutes" class="col-md-6 mb-3" required help="Check in after start + grace is counted as late." />
                <x-input name="early_leave_grace_minutes" type="number" label="Early Leave Grace (minutes)" :value="$shift->early_leave_grace_minutes" class="col-md-6 mb-3" required />
                <x-input name="half_day_minutes" type="number" label="Half Day if Worked Less Than (minutes)" :value="$shift->half_day_minutes" class="col-md-6 mb-3" required />
                <x-input name="break_minutes" type="number" label="Break (minutes)" :value="$shift->break_minutes" class="col-md-6 mb-3" required />
            </div>
            <x-checkbox name="is_default" label="Default shift" :checked="$shift->is_default" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.shifts.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
