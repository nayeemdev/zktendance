@extends('layouts.app')

@section('title', 'Loans & Advances')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <ul class="nav nav-pills">
        @foreach (['active', 'paused', 'completed', 'all'] as $status)
            <li class="nav-item"><a class="nav-link {{ request('status', 'active') === $status ? 'active' : '' }}" href="?status={{ $status }}">{{ ucfirst($status) }}</a></li>
        @endforeach
    </ul>
    <a href="{{ route('admin.loans.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Loan</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Reason</th><th class="text-end">Amount</th><th class="text-end">Installment</th><th class="text-end">Paid</th><th class="text-end">Remaining</th><th>Starts</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($loans as $loan)
                <tr>
                    <td>{{ $loan->employee->name }}</td>
                    <td class="small">{{ $loan->reason }}</td>
                    <td class="text-end">{{ money($loan->amount) }}</td>
                    <td class="text-end">{{ money($loan->installment) }}</td>
                    <td class="text-end">{{ money($loan->paid_amount) }}</td>
                    <td class="text-end">{{ money($loan->remaining()) }}</td>
                    <td>{{ $loan->start_month->format('M Y') }}</td>
                    <td><x-badge :status="$loan->status" /></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.loans.edit', $loan) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                        <x-delete-button :action="route('admin.loans.destroy', $loan)" />
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-muted">No loans.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $loans->links() }}</div>
@endsection
