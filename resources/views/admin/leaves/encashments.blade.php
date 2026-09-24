@extends('layouts.app')

@section('title', 'Leave Encashment')

@section('content')
<x-page-header subtitle="Unused leave days paid as an earning in the selected payroll month.">
    <a href="{{ route('admin.leave-encashments.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Encash Leave</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Leave Type</th><th>Year</th><th>Days</th><th class="text-end">Amount</th><th>Payroll Month</th><th>By</th><th></th></tr></thead>
            <tbody>
            @forelse ($encashments as $encashment)
                <tr>
                    <td>{{ $encashment->employee->name }}</td>
                    <td>{{ $encashment->leaveType->name }}</td>
                    <td>{{ $encashment->year }}</td>
                    <td>{{ $encashment->days }}</td>
                    <td class="text-end">{{ money($encashment->amount) }}</td>
                    <td>{{ $encashment->adjustment?->month->format('F Y') ?? '-' }}</td>
                    <td class="small">{{ $encashment->creator?->name }}</td>
                    <td class="text-end"><x-delete-button :action="route('admin.leave-encashments.destroy', $encashment)" message="Remove this encashment and give the days back?" /></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No encashments yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $encashments->links() }}</div>
@endsection
