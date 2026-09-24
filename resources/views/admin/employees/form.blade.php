@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Employee' : 'Add Employee')

@section('content')
<form method="POST" action="{{ $employee->exists ? route('admin.employees.update', $employee) : route('admin.employees.store') }}">
    @csrf
    @if ($employee->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Personal</div>
                <div class="card-body row">
                    <x-input name="name" label="Full Name" :value="$employee->name" class="col-md-6 mb-3" required />
                    <x-input name="email" type="email" label="Email" :value="$employee->email" class="col-md-6 mb-3" />
                    <x-input name="phone" label="Phone" :value="$employee->phone" class="col-md-4 mb-3" />
                    <x-select name="gender" label="Gender" :options="\App\Models\Employee::GENDERS" :value="$employee->gender" placeholder="Select" class="col-md-4 mb-3" />
                    <x-input name="date_of_birth" type="date" label="Date of Birth" :value="$employee->date_of_birth?->toDateString()" class="col-md-4 mb-3" />
                    <x-input name="nid" label="NID" :value="$employee->nid" class="col-md-6 mb-3" />
                    <x-input name="tin" label="TIN" :value="$employee->tin" class="col-md-6 mb-3" />
                    <x-textarea name="address" label="Address" :value="$employee->address" rows="2" class="col-12 mb-3" />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Job</div>
                <div class="card-body row">
                    <x-input name="employee_code" label="Employee Code" :value="$employee->employee_code" class="col-md-4 mb-3" required />
                    <x-input name="device_user_id" label="Device User ID" :value="$employee->device_user_id" class="col-md-4 mb-3" help="The user ID (PIN) on the ZKTeco device." />
                    <x-select name="employment_type" label="Employment Type" :options="\App\Models\Employee::EMPLOYMENT_TYPES" :value="$employee->employment_type" class="col-md-4 mb-3" required />
                    <x-select name="branch_id" label="Branch" :options="$branches" :value="$employee->branch_id" placeholder="Select" class="col-md-4 mb-3" required />
                    <x-select name="department_id" label="Department" :options="$departments" :value="$employee->department_id" placeholder="None" class="col-md-4 mb-3" />
                    <x-select name="designation_id" label="Designation" :options="$designations" :value="$employee->designation_id" placeholder="None" class="col-md-4 mb-3" />
                    <x-select name="shift_id" label="Shift" :options="$shifts" :value="$employee->shift_id" placeholder="Default shift" class="col-md-4 mb-3" />
                    <x-input name="joining_date" type="date" label="Joining Date" :value="$employee->joining_date?->toDateString()" class="col-md-4 mb-3" required />
                    <x-input name="leaving_date" type="date" label="Leaving Date" :value="$employee->leaving_date?->toDateString()" class="col-md-4 mb-3" />
                    <x-select name="status" label="Status" :options="\App\Models\Employee::STATUSES" :value="$employee->status" class="col-md-4 mb-3" required />
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Bank &amp; Tax</div>
                <div class="card-body">
                    <x-input name="bank_name" label="Bank Name" :value="$employee->bank_name" />
                    <x-input name="bank_account_no" label="Account Number" :value="$employee->bank_account_no" />
                    <x-checkbox name="tax_enabled" label="Deduct income tax (TDS)" :checked="$employee->tax_enabled" />
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header bg-white fw-semibold">Portal Login</div>
                <div class="card-body">
                    @if ($employee->user)
                        <p class="small text-muted">Login email: {{ $employee->user->email }}</p>
                        <input type="hidden" name="create_login" value="1">
                        <x-input name="password" type="password" label="Reset Password" help="Leave empty to keep the current password." />
                    @else
                        <x-checkbox name="create_login" label="Create login for employee portal" :checked="false" />
                        <x-input name="password" type="password" label="Password" help="Needs the email above. Minimum 8 characters." />
                    @endif
                </div>
            </div>

            <button class="btn btn-primary w-100">Save Employee</button>
        </div>
    </div>
</form>
@endsection
