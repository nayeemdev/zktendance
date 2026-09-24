@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="row g-3">
    @foreach ([
        ['admin.attendance.index', 'calendar-day', 'Daily Attendance', 'In and out times and status for any day.'],
        ['admin.reports.monthly-summary', 'table', 'Monthly Summary', 'Present, late, absent, leave and overtime per employee.'],
        ['admin.reports.monthly-sheet', 'grid-3x3', 'Monthly Attendance Sheet', 'Day by day status grid for the whole month.'],
        ['admin.reports.late', 'alarm', 'Late & Early Leave', 'Everyone who came late or left early in a date range.'],
        ['admin.leave-balances.index', 'bar-chart', 'Leave Balance', 'Remaining leave per employee and type.'],
        ['admin.payroll.index', 'cash-stack', 'Payroll Register', 'Open a payroll run to export salary and bank sheets.'],
    ] as [$route, $icon, $title, $text])
        <div class="col-md-6 col-xl-4">
            <a href="{{ route($route) }}" class="card h-100 text-decoration-none">
                <div class="card-body">
                    <i class="bi bi-{{ $icon }} fs-3 text-primary"></i>
                    <h6 class="mt-2 text-body">{{ $title }}</h6>
                    <p class="small text-muted mb-0">{{ $text }}</p>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
