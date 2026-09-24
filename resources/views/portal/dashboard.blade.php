@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-0">{{ $employee->name }}</h5>
                <div class="text-muted small">{{ $employee->designation?->name }} &middot; {{ $employee->department?->name }}</div>
                <div class="text-muted small">{{ $employee->branch->name }} &middot; {{ $employee->shift?->label() ?? 'Default shift' }}</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header bg-white fw-semibold">Today</div>
            <div class="card-body">
                @if ($today)
                    <p class="mb-2"><x-status :attendance="$today" /></p>
                @endif
                @forelse ($punches as $punch)
                    <div><i class="bi bi-fingerprint text-primary"></i> {{ $punch->punched_at->format('h:i:s A') }}</div>
                @empty
                    <p class="text-muted mb-0">No punch yet today.</p>
                @endforelse
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-white fw-semibold">Leave Balance {{ now()->year }}</div>
            <ul class="list-group list-group-flush">
                @foreach ($balances as $balance)
                    @if ($balance->leaveType->days_per_year > 0)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $balance->leaveType->name }}</span>
                            <strong>{{ $balance->remaining() }}</strong>
                        </li>
                    @endif
                @endforeach
            </ul>
            <div class="card-body"><a href="{{ route('portal.leaves.create') }}" class="btn btn-primary btn-sm w-100">Apply for Leave</a></div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            @foreach ([['Present', $summary['present'], 'success'], ['Late', $summary['late'], 'warning'], ['Absent', $summary['absent'], 'danger'], ['Leave', $summary['leave'] + $summary['unpaid_leave'], 'primary']] as [$label, $value, $color])
                <div class="col-6 col-md-3">
                    <div class="card card-body text-center">
                        <div class="fs-3 fw-semibold text-{{ $color }}">{{ $value }}</div>
                        <div class="small text-muted">{{ $label }} this month</div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="card">
            <div class="card-header bg-white fw-semibold">Notices</div>
            <div class="list-group list-group-flush">
                @forelse ($notices as $notice)
                    <div class="list-group-item">
                        <strong>{{ $notice->title }}</strong> <span class="small text-muted">{{ $notice->published_on->format('d M Y') }}</span>
                        <div class="small">{!! nl2br(e($notice->body)) !!}</div>
                    </div>
                @empty
                    <div class="list-group-item text-muted">No notices.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
