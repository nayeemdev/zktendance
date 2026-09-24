@extends('layouts.app')

@section('title', $leaveType->exists ? 'Edit Leave Type' : 'Add Leave Type')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        <form method="POST" action="{{ $leaveType->exists ? route('admin.leave-types.update', $leaveType) : route('admin.leave-types.store') }}">
            @csrf
            @if ($leaveType->exists) @method('PUT') @endif
            <div class="row">
                <x-input name="name" label="Name" :value="$leaveType->name" class="col-md-8 mb-3" required />
                <x-input name="code" label="Code" :value="$leaveType->code" class="col-md-4 mb-3" required />
                <x-input name="days_per_year" type="number" step="0.5" label="Days per Year" :value="$leaveType->days_per_year ?? 0" class="col-md-6 mb-3" required help="Use 0 for no limit, for example unpaid leave." />
                <x-input name="carry_forward_limit" type="number" step="0.5" label="Carry Forward Limit" :value="$leaveType->carry_forward_limit ?? 0" class="col-md-6 mb-3" required help="Unused days moved to next year, up to this number." />
            </div>
            <x-checkbox name="is_paid" label="Paid leave (no salary deduction)" :checked="$leaveType->is_paid" />
            <x-checkbox name="allow_half_day" label="Allow half day" :checked="$leaveType->allow_half_day" />
            <x-checkbox name="is_active" label="Active" :checked="$leaveType->is_active" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.leave-types.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
