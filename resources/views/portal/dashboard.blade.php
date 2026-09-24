@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
@php
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $start = today()->startOfMonth();
    $offset = (int) $start->format('w');
    $workedDays = $summary['present'] + $summary['absent'] + $summary['leave'] + $summary['unpaid_leave'];
    $rate = $workedDays > 0 ? round($summary['present'] / $workedDays * 100) : null;
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="hero h-100">
            <div class="position-relative d-flex flex-column flex-md-row justify-content-between gap-4" style="z-index: 1">
                <div>
                    <div class="hero-label">{{ today()->format('l, d F Y') }}</div>
                    <h2 class="h3 mt-1 mb-2">{{ $greeting }}, {{ strtok($employee->name, ' ') }}</h2>
                    <div class="small opacity-75 mb-3">
                        {{ $employee->designation?->name ?? 'Employee' }} &middot; {{ $employee->department?->name ?? $employee->branch->name }}
                        @if ($shift) &middot; {{ $shift->label() }} @endif
                    </div>
                    @if ($today)
                        <span class="badge bg-white text-dark fs-6">{{ $today->statusLabel() }}</span>
                    @else
                        <span class="badge bg-white text-dark fs-6">No punch yet</span>
                    @endif
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('portal.leaves.create') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-calendar-add-01 me-1"></i>Apply for leave</a>
                        <a href="{{ route('portal.corrections.create') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-task-edit-01 me-1"></i>Request correction</a>
                        <a href="{{ route('portal.payslips.index') }}" class="btn btn-sm btn-hero"><i class="hgi-stroke hgi-invoice-03 me-1"></i>Payslips</a>
                    </div>
                </div>
                <div class="text-md-end">
                    <div class="hero-label">Present this month</div>
                    <div class="hero-figure">{{ $rate !== null ? $rate.'%' : '-' }}</div>
                    <div class="small opacity-75">{{ rtrim(rtrim(number_format($summary['present'], 1), '0'), '.') }} of {{ rtrim(rtrim(number_format($workedDays, 1), '0'), '.') }} working days</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Today's punches</div>
            <div class="card-body">
                @if ($punches->isNotEmpty())
                    <div class="timeline">
                        @foreach ($punches as $i => $punch)
                            <div class="timeline-item">
                                <div class="fw-semibold">{{ $punch->punched_at->format('h:i A') }}</div>
                                <div class="small text-muted">{{ $i === 0 ? 'First punch' : ($loop->last ? 'Last punch' : 'Punch') }}{{ $punch->verify_type ? ' · '.$punch->verify_type : '' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state"><i class="hgi-stroke hgi-fingerprint-scan"></i>No punch yet today.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach ([
        ['Present', $summary['present'], 'user-check-01', 'green'],
        ['Late', $summary['late'], 'alarm-clock', 'amber'],
        ['Absent', $summary['absent'], 'user-remove-01', 'red'],
        ['On leave', $summary['leave'] + $summary['unpaid_leave'], 'beach', 'blue'],
        ['Overtime', minutes_to_hours($summary['ot_workday_minutes'] + $summary['ot_offday_minutes']), 'clock-04', 'indigo'],
    ] as [$label, $value, $icon, $tone])
        <div class="col-6 col-md">
            <div class="card stat-tile">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="stat-label">{{ $label }}</span>
                        <span class="stat-icon tone-{{ $tone }}"><i class="hgi-stroke hgi-{{ $icon }}"></i></span>
                    </div>
                    <div class="stat-value tabular">{{ is_numeric($value) ? rtrim(rtrim(number_format($value, 1), '0'), '.') : $value }}</div>
                    <div class="small text-muted">This month</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <span>{{ today()->format('F Y') }}</span>
                <a href="{{ route('portal.attendance') }}" class="small fw-normal">Details</a>
            </div>
            <div class="card-body">
                <div class="cal-grid mb-3">
                    @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $dow)
                        <div class="dow">{{ $dow }}</div>
                    @endforeach
                    @for ($i = 0; $i < $offset; $i++)
                        <div></div>
                    @endfor
                    @for ($d = 1; $d <= $start->daysInMonth; $d++)
                        @php($row = $month[$d] ?? null)
                        <div class="cal-cell {{ $row?->status }} {{ $d === today()->day ? 'today' : '' }}" title="{{ $start->copy()->day($d)->format('D, d M') }}{{ $row ? ': '.$row->statusLabel() : '' }}">{{ $d }}</div>
                    @endfor
                </div>
                <div class="legend">
                    <span><i style="background: #22c55e"></i>Present</span>
                    <span><i style="background: #f59e0b"></i>Late</span>
                    <span><i style="background: #ef4444"></i>Absent</span>
                    <span><i style="background: #3b82f6"></i>Leave</span>
                    <span><i style="border: 1px dashed var(--zk-muted)"></i>Off day</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-header">Leave balance {{ now()->year }}</div>
            <div class="card-body">
                @foreach ($balances as $balance)
                    @if ($balance->leaveType->days_per_year > 0)
                        @php($total = $balance->allocated + $balance->carried_forward)
                        @php($left = $total > 0 ? $balance->remaining() / $total * 100 : 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="fw-semibold">{{ $balance->leaveType->name }}</span>
                                <span class="text-muted tabular">{{ $balance->remaining() }} / {{ $total }} left</span>
                            </div>
                            <div class="meter {{ $left < 20 ? 'danger' : ($left < 50 ? 'warn' : '') }}" role="img" aria-label="{{ $balance->remaining() }} of {{ $total }} days left"><span style="width: {{ max(0, min(100, $left)) }}%"></span></div>
                        </div>
                    @endif
                @endforeach
                <a href="{{ route('portal.leaves.create') }}" class="btn btn-primary w-100 mt-2"><i class="hgi-stroke hgi-calendar-add-01 me-1"></i>Apply for Leave</a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-header">Notices</div>
            <div class="card-body pt-2">
                @forelse ($notices as $notice)
                    <div class="rank-row align-items-start">
                        <span class="stat-icon tone-indigo" style="width: 32px; height: 32px"><i class="hgi-stroke hgi-megaphone-01"></i></span>
                        <span class="min-w-0">
                            <span class="d-block fw-semibold small">{{ $notice->title }}</span>
                            <span class="d-block small text-muted mb-1">{{ $notice->published_on->format('d M Y') }}</span>
                            <span class="d-block small">{!! nl2br(e(\Illuminate\Support\Str::limit($notice->body, 180))) !!}</span>
                        </span>
                    </div>
                @empty
                    <div class="empty-state"><i class="hgi-stroke hgi-megaphone-01"></i>No notices right now.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
