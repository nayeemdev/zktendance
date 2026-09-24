@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="row g-3">
    @foreach ([
        ['admin.attendance.index', 'calendar-01', 'Daily Attendance', 'In and out times and status for any day.'],
        ['admin.reports.monthly-summary', 'table-01', 'Monthly Summary', 'Present, late, absent, leave and overtime per employee.'],
        ['admin.reports.monthly-sheet', 'grid-table', 'Monthly Attendance Sheet', 'Day by day status grid for the whole month.'],
        ['admin.reports.late', 'alarm-clock', 'Late & Early Leave', 'Everyone who came late or left early in a date range.'],
        ['admin.leave-balances.index', 'chart-histogram', 'Leave Balance', 'Remaining leave per employee and type.'],
        ['admin.payroll.index', 'money-bag-02', 'Payroll Register', 'Open a payroll run to export salary and bank sheets.'],
    ] as [$route, $icon, $title, $text])
        <div class="col-md-6 col-xl-4">
            <a href="{{ route($route) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <span class="stat-icon tone-indigo"><i class="hgi-stroke hgi-{{ $icon }}"></i></span>
                    <h6 class="mt-2 text-body">{{ $title }}</h6>
                    <p class="small text-muted mb-0">{{ $text }}</p>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
