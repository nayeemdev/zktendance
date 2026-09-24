@extends('layouts.app')

@section('title', 'Payroll '.$run->title())

@section('content')
<x-page-header :title="$run->title()">
    <a href="{{ route('admin.payroll.export', [$run, 'format' => 'xlsx']) }}" class="btn btn-outline-success"><i class="hgi-stroke hgi-xls-02"></i> Excel</a>
    <a href="{{ route('admin.payroll.export', $run) }}" class="btn btn-outline-secondary"><i class="hgi-stroke hgi-csv-02"></i> CSV</a>
    @if ($run->isDraft())
        <x-post-button :action="route('admin.payroll.regenerate', $run)" label="Regenerate" icon="refresh" style="outline-primary" confirm="Recalculate all payslips from the latest data? Manual line edits will be replaced." />
        <x-post-button :action="route('admin.payroll.approve', $run)" label="Approve" icon="checkmark-circle-02" style="success" confirm="Approve this payroll? Loan installments will be recorded and payslips will be visible to employees." />
        <x-delete-button :action="route('admin.payroll.destroy', $run)" message="Delete this draft payroll?" label="Delete" />
    @else
        <x-post-button :action="route('admin.payroll.email', $run)" label="Email Payslips" icon="mail-01" confirm="Email payslips to all employees in this run?" />
        @if ($run->status === 'approved')
            <x-post-button :action="route('admin.payroll.pay', $run)" label="Mark as Paid" icon="money-01" style="success" />
        @endif
    @endif
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card card-body"><div class="small text-muted">Status</div><div class="fs-5"><x-badge :status="$run->status" /></div></div></div>
    <div class="col-md-3"><div class="card card-body"><div class="small text-muted">Total Earnings</div><div class="fs-5">{{ money($run->total_gross) }}</div></div></div>
    <div class="col-md-3"><div class="card card-body"><div class="small text-muted">Total Deductions</div><div class="fs-5">{{ money($run->total_deductions) }}</div></div></div>
    <div class="col-md-3"><div class="card card-body"><div class="small text-muted">Net Payable</div><div class="fs-5 fw-semibold">{{ money($run->total_net) }}</div></div></div>
</div>

@if ($run->approver)
    <p class="small text-muted">Approved by {{ $run->approver->name }} on {{ $run->approved_at->format('d M Y h:i A') }}@if($run->paid_at), paid on {{ $run->paid_at->format('d M Y') }}@endif</p>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead><tr><th>Employee</th><th>Present</th><th>Absent</th><th>Leave</th><th>Late</th><th>OT hrs</th><th class="text-end">Earnings</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th></th></tr></thead>
            <tbody>
            @forelse ($payslips as $payslip)
                <tr>
                    <td>{{ $payslip->employee->name }} @if($payslip->edited_at)<span class="badge text-bg-warning">Edited</span>@endif<div class="small text-muted">{{ $payslip->employee->employee_code }} &middot; {{ $payslip->employee->designation?->name }}</div></td>
                    <td>{{ $payslip->present_days }}</td>
                    <td>{{ $payslip->absent_days + $payslip->unpaid_leave_days }}</td>
                    <td>{{ $payslip->paid_leave_days }}</td>
                    <td>{{ $payslip->late_days }}</td>
                    <td>{{ $payslip->overtime_hours }}</td>
                    <td class="text-end">{{ money($payslip->total_earnings, false) }}</td>
                    <td class="text-end">{{ money($payslip->total_deductions, false) }}</td>
                    <td class="text-end fw-semibold">{{ money($payslip->net_salary, false) }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.payslips.show', $payslip) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-view"></i></a>
                        <a href="{{ route('admin.payslips.pdf', $payslip) }}" class="btn btn-sm btn-outline-secondary"><i class="hgi-stroke hgi-pdf-02"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-muted">No payslips. Check that employees have a salary set.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
