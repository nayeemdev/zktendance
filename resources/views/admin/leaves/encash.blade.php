@extends('layouts.app')

@section('title', 'Encash Leave')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        @if ($leaveTypes->isEmpty())
            <div class="alert alert-warning">No leave type allows encashment. Turn it on in Leave Types first.</div>
        @endif
        <form method="POST" action="{{ route('admin.leave-encashments.store') }}">
            @csrf
            <x-select name="employee_id" label="Employee" :options="$employees" placeholder="Select" required />
            <div class="row">
                <x-select name="leave_type_id" label="Leave Type" :options="$leaveTypes" placeholder="Select" class="col-md-6 mb-3" required />
                <x-input name="year" type="number" label="Balance Year" :value="now()->year" class="col-md-6 mb-3" required />
                <x-input name="days" type="number" step="0.5" label="Days to Encash" class="col-md-6 mb-3" required />
                <x-input name="month" type="month" label="Pay in Payroll Month" :value="now()->format('Y-m')" class="col-md-6 mb-3" required />
            </div>
            <p class="small text-muted">Amount = {{ setting('encashment_base') === 'gross' ? 'gross' : 'basic' }} salary / 30 x days. It is added as a bonus line, so regenerate the draft payroll of that month.</p>
            <button class="btn btn-primary">Encash</button>
        </form>
    </div>
</div>
@endsection
