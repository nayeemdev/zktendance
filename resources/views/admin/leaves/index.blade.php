@extends('layouts.app')

@section('title', 'Leave Requests')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <ul class="nav nav-pills">
        @foreach (['pending', 'approved', 'rejected', 'cancelled', 'all'] as $status)
            <li class="nav-item"><a class="nav-link {{ request('status', 'pending') === $status ? 'active' : '' }}" href="?status={{ $status }}">{{ ucfirst($status) }}</a></li>
        @endforeach
    </ul>
    <a href="{{ route('admin.leaves.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Leave</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($leaves as $leave)
                <tr>
                    <td>{{ $leave->employee->name }}<div class="small text-muted">{{ $leave->employee->employee_code }}</div></td>
                    <td>{{ $leave->leaveType->name }}</td>
                    <td>{{ $leave->start_date->format('d M') }}@if(! $leave->start_date->eq($leave->end_date)) - {{ $leave->end_date->format('d M Y') }}@else {{ $leave->start_date->format('Y') }}@endif @if($leave->is_half_day)<span class="badge text-bg-info">Half</span>@endif</td>
                    <td>{{ $leave->days }}</td>
                    <td class="small">{{ $leave->reason }}</td>
                    <td><x-badge :status="$leave->status" />@if($leave->reviewer)<div class="small text-muted">by {{ $leave->reviewer->name }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        @if ($leave->status === 'pending')
                            <x-post-button :action="route('admin.leaves.approve', $leave)" label="Approve" style="success" />
                            <x-post-button :action="route('admin.leaves.reject', $leave)" label="Reject" style="outline-danger" />
                        @elseif ($leave->status === 'approved')
                            <x-post-button :action="route('admin.leaves.cancel', $leave)" label="Cancel" style="outline-secondary" confirm="Cancel this approved leave and give the days back?" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No leave requests.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $leaves->links() }}</div>
@endsection
