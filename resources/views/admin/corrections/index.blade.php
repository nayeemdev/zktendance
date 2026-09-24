@extends('layouts.app')

@section('title', 'Attendance Corrections')

@section('content')
<ul class="nav nav-pills mb-3">
    @foreach (['pending', 'approved', 'rejected'] as $status)
        <li class="nav-item"><a class="nav-link {{ request('status', 'pending') === $status ? 'active' : '' }}" href="?status={{ $status }}">{{ ucfirst($status) }}</a></li>
    @endforeach
</ul>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($corrections as $correction)
                <tr>
                    <td>{{ $correction->employee->name }}</td>
                    <td>{{ $correction->date->format('d M Y') }}</td>
                    <td>{{ $correction->check_in ? substr($correction->check_in, 0, 5) : '-' }}</td>
                    <td>{{ $correction->check_out ? substr($correction->check_out, 0, 5) : '-' }}</td>
                    <td>{{ $correction->reason }}</td>
                    <td><x-badge :status="$correction->status" />@if($correction->reviewer)<div class="small text-muted">by {{ $correction->reviewer->name }}</div>@endif</td>
                    <td class="text-end text-nowrap">
                        @if ($correction->status === 'pending')
                            <x-post-button :action="route('admin.corrections.approve', $correction)" label="Approve" style="success" />
                            <x-post-button :action="route('admin.corrections.reject', $correction)" label="Reject" style="outline-danger" />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted">Nothing here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $corrections->links() }}</div>
@endsection
