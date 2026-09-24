@extends('layouts.app')

@section('title', 'Payroll Runs')

@section('content')
<x-page-header subtitle="Payroll uses processed attendance, leave, overtime, loans, bonus and tax for the month.">
    <a href="{{ route('admin.payroll.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Run Payroll</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Month</th><th>Branch</th><th>Employees</th><th class="text-end">Earnings</th><th class="text-end">Deductions</th><th class="text-end">Net Pay</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($runs as $run)
                <tr>
                    <td><a href="{{ route('admin.payroll.show', $run) }}">{{ $run->month->format('F Y') }}</a></td>
                    <td>{{ $run->branch?->name ?? 'All Branches' }}</td>
                    <td>{{ $run->payslips_count }}</td>
                    <td class="text-end">{{ money($run->total_gross) }}</td>
                    <td class="text-end">{{ money($run->total_deductions) }}</td>
                    <td class="text-end fw-semibold">{{ money($run->total_net) }}</td>
                    <td><x-badge :status="$run->status" /></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No payroll yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $runs->links() }}</div>
@endsection
