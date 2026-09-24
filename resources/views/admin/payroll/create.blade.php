@extends('layouts.app')

@section('title', 'Run Payroll')

@section('content')
<div class="card col-lg-6">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.payroll.store') }}">
            @csrf
            <x-input name="month" type="month" label="Salary Month" :value="now()->subMonth()->format('Y-m')" required />
            <x-select name="branch_id" label="Branch" :options="$branches" placeholder="All Branches" />
            <div class="alert alert-info small">
                Before running payroll check that:
                <ul class="mb-0">
                    <li>All devices are synced and corrections are approved.</li>
                    <li>Leave requests for the month are approved.</li>
                    <li>Every employee has a salary set.</li>
                    <li>Bonuses and deductions for the month are added.</li>
                </ul>
                The run is a draft first. You can regenerate it until you approve it.
            </div>
            <button class="btn btn-primary">Generate Payroll</button>
        </form>
    </div>
</div>
@endsection
