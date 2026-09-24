@extends('layouts.app')

@section('title', 'Add Bonus or Deduction')

@section('content')
<div class="card col-lg-7">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.adjustments.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Employees <span class="text-danger">*</span></label>
                <select name="employee_ids[]" class="form-select" multiple size="10" required>
                    @foreach ($employees as $id => $name)
                        <option value="{{ $id }}" @selected(in_array($id, old('employee_ids', [])))>{{ $name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Hold Ctrl (or Cmd) to select several. Use Ctrl+A to select all.</div>
            </div>
            <div class="row">
                <x-input name="month" type="month" label="Month" :value="$adjustment->month->format('Y-m')" class="col-md-4 mb-3" required />
                <x-select name="type" label="Type" :options="['earning' => 'Earning (Bonus)', 'deduction' => 'Deduction']" :value="$adjustment->type" class="col-md-4 mb-3" required />
                <x-input name="amount" type="number" step="0.01" label="Amount" class="col-md-4 mb-3" required />
            </div>
            <x-input name="title" label="Title" placeholder="Eid Bonus" required />
            <button class="btn btn-primary">Save</button>
            <a href="{{ route('admin.adjustments.index') }}" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
@endsection
