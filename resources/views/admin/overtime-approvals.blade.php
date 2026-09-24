@extends('layouts.app')

@section('title', 'Overtime Approvals')

@section('content')
<form class="card card-body mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="form-control"></div>
        <x-select name="status" :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']" :value="request('status', 'pending')" class="col-md-3" />
        <x-select name="branch_id" :options="$branches" :value="request('branch_id')" placeholder="All Branches" class="col-md-3" />
        <div class="col-md-3"><button class="btn btn-secondary w-100">Show</button></div>
    </div>
</form>

<form method="POST" action="{{ route('admin.overtime.review') }}">
    @csrf
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.ot-check').forEach(c => c.checked = this.checked)"></th>
                    <th>Date</th><th>Employee</th><th>Branch</th><th>In</th><th>Out</th><th>Status</th><th>Overtime</th><th>Reviewed By</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td><input type="checkbox" class="form-check-input ot-check" name="ids[]" value="{{ $row->id }}"></td>
                        <td>{{ $row->date->format('d M, D') }}</td>
                        <td>{{ $row->employee->name }}</td>
                        <td>{{ $row->employee->branch->name }}</td>
                        <td>{{ $row->check_in?->format('h:i A') }}</td>
                        <td>{{ $row->check_out?->format('h:i A') }}</td>
                        <td><x-status :attendance="$row" /></td>
                        <td class="fw-semibold">{{ minutes_to_hours($row->overtime_minutes) }}</td>
                        <td class="small">{{ $row->overtimeReviewer?->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-muted">No overtime to show.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($rows->isNotEmpty())
        <div class="mt-3 d-flex gap-2">
            <button name="decision" value="approved" class="btn btn-success"><i class="hgi-stroke hgi-tick-02"></i> Approve Selected</button>
            <button name="decision" value="rejected" class="btn btn-outline-danger"><i class="hgi-stroke hgi-cancel-01"></i> Reject Selected</button>
        </div>
    @endif
</form>
@endsection
