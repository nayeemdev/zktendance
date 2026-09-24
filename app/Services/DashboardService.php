<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Device;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function adminStats(?int $branchId = null): array
    {
        $today = today();
        $employees = Employee::active()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $todayRows = Attendance::whereDate('date', $today)
            ->whereIn('employee_id', (clone $employees)->select('id'))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $present = ($todayRows[Attendance::PRESENT] ?? 0) + ($todayRows[Attendance::LATE] ?? 0) + ($todayRows[Attendance::HALF_DAY] ?? 0);

        return [
            'employees' => (clone $employees)->count(),
            'present' => $present,
            'late' => $todayRows[Attendance::LATE] ?? 0,
            'absent' => $todayRows[Attendance::ABSENT] ?? 0,
            'on_leave' => ($todayRows[Attendance::LEAVE] ?? 0) + ($todayRows[Attendance::UNPAID_LEAVE] ?? 0),
            'pending_leaves' => LeaveRequest::whereIn('status', ['pending', 'recommended'])->whereIn('employee_id', (clone $employees)->select('id'))->count(),
            'pending_corrections' => AttendanceCorrection::where('status', 'pending')->whereIn('employee_id', (clone $employees)->select('id'))->count(),
            'devices' => Device::with('branch')->where('is_active', true)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get(),
            'chart' => $this->lastDays(14, $branchId),
        ];
    }

    public function lastDays(int $days, ?int $branchId = null): array
    {
        $from = today()->subDays($days - 1);

        $rows = Attendance::whereDate('date', '>=', $from)
            ->when($branchId, fn ($q) => $q->whereHas('employee', fn ($e) => $e->where('branch_id', $branchId)))
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $labels = $present = $absent = $late = [];
        for ($date = $from->copy(); $date->lte(today()); $date->addDay()) {
            $day = $rows[$date->toDateString()] ?? collect();
            $labels[] = $date->format('d M');
            $present[] = (int) $day->whereIn('status', [Attendance::PRESENT, Attendance::HALF_DAY])->sum('total');
            $late[] = (int) $day->where('status', Attendance::LATE)->sum('total');
            $absent[] = (int) $day->where('status', Attendance::ABSENT)->sum('total');
        }

        return compact('labels', 'present', 'late', 'absent');
    }
}
