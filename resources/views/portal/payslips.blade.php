@extends('layouts.app')

@section('title', 'My Payslips')

@section('content')
<div class="card col-lg-9">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Month</th><th class="text-end">Earnings</th><th class="text-end">Deductions</th><th class="text-end">Net Pay</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($payslips as $payslip)
                <tr>
                    <td>{{ $payslip->payrollRun->month->format('F Y') }}</td>
                    <td class="text-end">{{ money($payslip->total_earnings) }}</td>
                    <td class="text-end">{{ money($payslip->total_deductions) }}</td>
                    <td class="text-end fw-semibold">{{ money($payslip->net_salary) }}</td>
                    <td><x-badge :status="$payslip->payrollRun->status" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('portal.payslips.show', $payslip) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('portal.payslips.pdf', $payslip) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-pdf"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted">No payslips yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $payslips->links() }}</div>
@endsection
