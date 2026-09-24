@extends('layouts.app')

@section('title', 'Assign Shifts')

@section('content')
<form method="POST" action="{{ route('admin.roster.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Who</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Employees</label>
                        <select name="employee_ids[]" class="form-select" multiple size="10">
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected(in_array($id, old('employee_ids', [])))>{{ $name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl (or Cmd) to select several, or leave empty and pick a branch or department.</div>
                    </div>
                    <x-select name="branch_id" label="Or whole branch" :options="$branches" placeholder="None" />
                    <x-select name="department_id" label="Or whole department" :options="$departments" placeholder="None" />
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Shift and dates</div>
                <div class="card-body">
                    <div class="row">
                        <x-input name="start_date" type="date" label="From" :value="today()->toDateString()" class="col-md-6 mb-3" required />
                        <x-input name="end_date" type="date" label="To" :value="today()->addMonth()->toDateString()" class="col-md-6 mb-3" required />
                    </div>
                    <x-select name="shift_ids[]" id="shift1" label="Shift" :options="$shifts" placeholder="Select" required />
                    <x-select name="shift_ids[]" id="shift2" label="Rotate with (optional)" :options="$shifts" placeholder="No rotation" />
                    <x-select name="shift_ids[]" id="shift3" label="Then (optional)" :options="$shifts" placeholder="No third shift" />
                    <x-input name="rotate_days" type="number" label="Change shift every (days)" value="7" help="Only used when more than one shift is selected." />
                    <x-input name="note" label="Note" placeholder="Ramadan schedule" />
                    <button class="btn btn-primary">Save Roster</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
