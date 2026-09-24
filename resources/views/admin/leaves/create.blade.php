@extends('layouts.app')

@section('title', 'Add Leave')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.leaves.store') }}">
            @csrf
            <x-select name="employee_id" label="Employee" :options="$employees" placeholder="Select" required />
            <x-select name="leave_type_id" label="Leave Type" :options="$leaveTypes" placeholder="Select" required />
            @include('partials.leave-dates')
            <x-textarea name="reason" label="Reason" />
            <x-checkbox name="approve_now" label="Approve immediately" :checked="true" />
            <button class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
