@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<x-page-header>
    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> Add Employee</a>
</x-page-header>

<form class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, code or device ID"></div>
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <x-select name="department_id" :options="$departments" :value="request('department_id')" placeholder="All Departments" class="col-md-3" />
        <x-select name="status" :options="\App\Models\Employee::STATUSES" :value="request('status')" placeholder="Any Status" class="col-md-2" />
        <div class="col-md-1"><button class="btn btn-secondary w-100"><i class="hgi-stroke hgi-search-01"></i></button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Name</th><th>Device ID</th><th>Branch</th><th>Department</th><th>Designation</th><th class="text-end">Gross Salary</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_code }}</td>
                    <td><a href="{{ route('admin.employees.show', $employee) }}">{{ $employee->name }}</a></td>
                    <td>{{ $employee->device_user_id ?? '-' }}</td>
                    <td>{{ $employee->branch->name }}</td>
                    <td>{{ $employee->department?->name }}</td>
                    <td>{{ $employee->designation?->name }}</td>
                    <td class="text-end">{{ $employee->currentSalary ? money($employee->currentSalary->gross_salary) : '-' }}</td>
                    <td><x-badge :status="$employee->status" /></td>
                    <td class="text-end"><a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-edit-02"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No employees found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
