@extends('layouts.app')

@section('title', 'My Leaves')

@section('content')
<x-page-header>
    <a href="{{ route('portal.leaves.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Apply for Leave</a>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach ($balances as $balance)
        <div class="col-6 col-md-3">
            <div class="card card-body">
                <div class="small text-muted">{{ $balance->leaveType->name }}</div>
                @if ($balance->leaveType->days_per_year > 0)
                    <div class="fs-4 fw-semibold">{{ $balance->remaining() }} <span class="fs-6 text-muted">/ {{ $balance->allocated + $balance->carried_forward }}</span></div>
                @else
                    <div class="fs-6">Used {{ $balance->used }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($leaves as $leave)
                <tr>
                    <td>{{ $leave->leaveType->name }}</td>
                    <td>{{ $leave->start_date->format('d M Y') }}</td>
                    <td>{{ $leave->end_date->format('d M Y') }}</td>
                    <td>{{ $leave->days }}</td>
                    <td class="small">{{ $leave->reason }}</td>
                    <td><x-badge :status="$leave->status" />@if($leave->review_note)<div class="small text-muted">{{ $leave->review_note }}</div>@endif</td>
                    <td class="text-end">
                        @if ($leave->status === 'pending')
                            <x-post-button :action="route('portal.leaves.cancel', $leave)" label="Cancel" style="outline-secondary" confirm="Cancel this request?" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">No leave requests yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $leaves->links() }}</div>
@endsection
