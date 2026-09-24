@extends('layouts.app')

@section('title', $employee->name)

@section('content')
<x-page-header :title="$employee->name" :subtitle="$employee->employee_code.' · '.($employee->designation?->name ?? 'No designation').' · '.$employee->branch->name">
    <a href="{{ route('admin.attendance.create', ['employee_id' => $employee->id]) }}" class="btn btn-outline-secondary"><i class="bi bi-calendar-plus"></i> Manual Attendance</a>
    <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
    <x-delete-button :action="route('admin.employees.destroy', $employee)" message="Delete this employee and all attendance, leave and payslip records?" label="Delete" />
</x-page-header>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Device ID</dt><dd class="col-7">{{ $employee->device_user_id ?? 'Not set' }}</dd>
                    <dt class="col-5">Department</dt><dd class="col-7">{{ $employee->department?->name ?? '-' }}</dd>
                    <dt class="col-5">Shift</dt><dd class="col-7">{{ $employee->shift?->label() ?? 'Default' }}</dd>
                    <dt class="col-5">Joined</dt><dd class="col-7">{{ $employee->joining_date->format('d M Y') }}</dd>
                    <dt class="col-5">Type</dt><dd class="col-7">{{ \App\Models\Employee::EMPLOYMENT_TYPES[$employee->employment_type] ?? '' }}</dd>
                    <dt class="col-5">Status</dt><dd class="col-7"><x-badge :status="$employee->status" /></dd>
                    <dt class="col-5">Email</dt><dd class="col-7">{{ $employee->email ?? '-' }}</dd>
                    <dt class="col-5">Phone</dt><dd class="col-7">{{ $employee->phone ?? '-' }}</dd>
                    <dt class="col-5">Bank</dt><dd class="col-7">{{ $employee->bank_name }} {{ $employee->bank_account_no }}</dd>
                    <dt class="col-5">Portal Login</dt><dd class="col-7">{{ $employee->user ? 'Yes' : 'No' }}</dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold">Leave Balance {{ now()->year }}</div>
            <ul class="list-group list-group-flush">
                @forelse ($balances as $balance)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $balance->leaveType->name }}</span>
                        <span>{{ $balance->remaining() }} / {{ $balance->allocated + $balance->carried_forward }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No balances yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Salary</div>
            <div class="card-body">
                @if ($current = $employee->salaries->first())
                    <p class="mb-2">Gross <strong>{{ money($current->gross_salary) }}</strong> using <strong>{{ $current->structure->name }}</strong> from {{ $current->effective_from->format('d M Y') }}</p>
                    <table class="table table-sm">
                        @foreach ($breakdown as $line)
                            <tr><td>{{ $line['name'] }}</td><td class="text-muted small">{{ ucfirst($line['type']) }}</td><td class="text-end">{{ money($line['amount']) }}</td></tr>
                        @endforeach
                    </table>
                @else
                    <p class="text-warning">No salary set. This employee will be skipped in payroll.</p>
                @endif

                <form method="POST" action="{{ route('admin.employees.salaries.store', $employee) }}" class="row g-2 align-items-end border-top pt-3">
                    @csrf
                    <x-select name="salary_structure_id" label="Structure" :options="$structures" class="col-md-3" required />
                    <x-input name="gross_salary" type="number" step="0.01" label="Gross Salary" class="col-md-3" required />
                    <x-input name="effective_from" type="date" label="Effective From" :value="today()->toDateString()" class="col-md-3" required />
                    <div class="col-md-3"><button class="btn btn-primary w-100">{{ $current ? 'Add Increment' : 'Set Salary' }}</button></div>
                    <x-input name="note" placeholder="Note, for example Yearly increment" class="col-12" />
                </form>

                @if ($employee->salaries->count() > 1)
                    <h6 class="mt-3">History</h6>
                    <table class="table table-sm">
                        @foreach ($employee->salaries as $salary)
                            <tr>
                                <td>{{ $salary->effective_from->format('d M Y') }}</td>
                                <td>{{ money($salary->gross_salary) }}</td>
                                <td>{{ $salary->structure->name }}</td>
                                <td class="text-muted small">{{ $salary->note }}</td>
                                <td class="text-end"><x-delete-button :action="route('admin.salaries.destroy', $salary)" /></td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Documents</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                    @forelse ($employee->documents as $document)
                        <tr>
                            <td><a href="{{ route('admin.documents.download', $document) }}"><i class="bi bi-file-earmark"></i> {{ $document->title }}</a><div class="small text-muted">{{ $document->original_name }} &middot; {{ number_format($document->size / 1024) }} KB</div></td>
                            <td class="small">
                                @if ($document->expires_on)
                                    <span class="badge text-bg-{{ $document->isExpired() ? 'danger' : ($document->expiresSoon() ? 'warning' : 'light') }}">{{ $document->isExpired() ? 'Expired' : 'Expires' }} {{ $document->expires_on->format('d M Y') }}</span>
                                @endif
                                @unless ($document->visible_to_employee)<span class="badge text-bg-secondary">HR only</span>@endunless
                            </td>
                            <td class="small text-muted">{{ $document->uploader?->name }}</td>
                            <td class="text-end"><x-delete-button :action="route('admin.documents.destroy', $document)" message="Delete this document?" /></td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No documents.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <form method="POST" action="{{ route('admin.employees.documents.store', $employee) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
                    @csrf
                    <x-input name="title" label="Title" placeholder="NID, CV, Contract" class="col-md-3" required />
                    <x-input name="file" type="file" label="File" class="col-md-4" required />
                    <x-input name="expires_on" type="date" label="Expires On" class="col-md-2" />
                    <div class="col-md-3"><button class="btn btn-primary w-100">Upload</button></div>
                    <x-checkbox name="visible_to_employee" label="Employee can see this document" :checked="true" class="col-12 mt-2" />
                </form>
                <div class="form-text">PDF, image, Word or Excel, up to 5 MB.</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white fw-semibold">Recent Attendance</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Worked</th><th>Late</th><th>OT</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($recent as $row)
                        <tr>
                            <td>{{ $row->date->format('d M, D') }}</td>
                            <td>{{ $row->check_in?->format('h:i A') ?? '-' }}</td>
                            <td>{{ $row->check_out?->format('h:i A') ?? '-' }}</td>
                            <td>{{ minutes_to_hours($row->worked_minutes) }}</td>
                            <td>{{ $row->late_minutes ?: '-' }}</td>
                            <td>{{ $row->overtime_minutes ? minutes_to_hours($row->overtime_minutes) : '-' }}</td>
                            <td><x-status :attendance="$row" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No attendance yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
