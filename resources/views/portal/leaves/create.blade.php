@extends('layouts.app')

@section('title', 'Apply for Leave')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        <form method="POST" action="{{ route('portal.leaves.store') }}">
            @csrf
            <x-select name="leave_type_id" label="Leave Type" :options="$leaveTypes->pluck('name', 'id')" placeholder="Select" required />
            @include('partials.leave-dates')
            <x-textarea name="reason" label="Reason" />
            <p class="small text-muted">Weekends and holidays inside the dates are not counted.</p>
            <button class="btn btn-primary">Submit</button>
            <a href="{{ route('portal.leaves.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
