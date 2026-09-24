@extends('layouts.app')

@section('title', 'Attendance Corrections')

@section('content')
<x-page-header subtitle="Forgot to punch or the device missed you? Ask HR to fix it.">
    <a href="{{ route('portal.corrections.create') }}" class="btn btn-primary"><i class="hgi-stroke hgi-add-01"></i> New Request</a>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Reason</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($corrections as $correction)
                <tr>
                    <td>{{ $correction->date->format('d M Y') }}</td>
                    <td>{{ $correction->check_in ? substr($correction->check_in, 0, 5) : '-' }}</td>
                    <td>{{ $correction->check_out ? substr($correction->check_out, 0, 5) : '-' }}</td>
                    <td>{{ $correction->reason }}</td>
                    <td><x-badge :status="$correction->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No requests.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $corrections->links() }}</div>
@endsection
