@extends('layouts.app')

@section('title', 'Request Attendance Correction')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ route('portal.corrections.store') }}">
            @csrf
            <x-input name="date" type="date" label="Date" :value="$date" required />
            <div class="row">
                <x-input name="check_in" type="time" label="Actual Check In" class="col-md-6 mb-3" />
                <x-input name="check_out" type="time" label="Actual Check Out" class="col-md-6 mb-3" />
            </div>
            <x-textarea name="reason" label="Reason" required />
            <button class="btn btn-primary">Submit</button>
            <a href="{{ route('portal.corrections.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
