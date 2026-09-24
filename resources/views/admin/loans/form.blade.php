@extends('layouts.app')

@section('title', $loan->exists ? 'Edit Loan' : 'Add Loan')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        <form method="POST" action="{{ $loan->exists ? route('admin.loans.update', $loan) : route('admin.loans.store') }}">
            @csrf
            @if ($loan->exists) @method('PUT') @endif
            <x-select name="employee_id" label="Employee" :options="$employees" :value="$loan->employee_id" placeholder="Select" required />
            <div class="row">
                <x-input name="amount" type="number" step="0.01" label="Loan Amount" :value="$loan->amount" class="col-md-4 mb-3" required />
                <x-input name="installment" type="number" step="0.01" label="Monthly Installment" :value="$loan->installment" class="col-md-4 mb-3" required />
                <x-input name="start_month" type="month" label="Deduct From" :value="$loan->start_month?->format('Y-m')" class="col-md-4 mb-3" required />
                @if ($loan->exists)
                    <x-input name="paid_amount" type="number" step="0.01" label="Already Paid" :value="$loan->paid_amount" class="col-md-6 mb-3" />
                @endif
                <x-select name="status" label="Status" :options="['active' => 'Active', 'paused' => 'Paused', 'completed' => 'Completed']" :value="$loan->status ?? 'active'" class="col-md-6 mb-3" required />
            </div>
            <x-input name="reason" label="Reason" :value="$loan->reason" />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.loans.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
