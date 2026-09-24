<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Department;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    private const IN = [Attendance::PRESENT, Attendance::LATE, Attendance::HALF_DAY];

    private const OFF = [Attendance::HOLIDAY, Attendance::WEEKEND];

    private const LEAVE = [Attendance::LEAVE, Attendance::UNPAID_LEAVE];

    public function adminStats(?int $branchId = null): array
    {
        $employees = Employee::active()->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $employeeIds = (clone $employees)->pluck('id');

        $today = $this->dayBreakdown(today(), $employeeIds);
        $yesterday = $this->dayBreakdown(today()->subDay(), $employeeIds);

        return [
            'employees' => $employeeIds->count(),
            'today' => $today,
            'yesterday' => $yesterday,
            'pending_leaves' => LeaveRequest::whereIn('status', ['pending', 'recommended'])->whereIn('employee_id', $employeeIds)->count(),
            'pending_corrections' => AttendanceCorrection::where('status', 'pending')->whereIn('employee_id', $employeeIds)->count(),
            'pending_overtime' => Attendance::where('overtime_status', 'pending')->whereIn('employee_id', $employeeIds)->count(),
            'devices' => Device::with('branch')->where('is_active', true)->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get(),
            'chart' => $this->lastDays(14, $employeeIds),
            'departments' => $this->departments($employeeIds),
            'payroll' => $this->payrollTrend($branchId),
            'late_leaders' => $this->lateLeaders($employeeIds),
            'holidays' => Holiday::with('branch')
                ->whereDate('date', '>=', today())
                ->whereDate('date', '<=', today()->addDays(45))
                ->when($branchId, fn ($q) => $q->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId)))
                ->orderBy('date')
                ->limit(5)
                ->get(),
            'birthdays' => $this->birthdays((clone $employees)->whereNotNull('date_of_birth')->get()),
        ];
    }

    /**
     * @return array{in: int, late: int, not_in: int, leave: int, off: int, expected: int, rate: ?float}
     */
    public function dayBreakdown(Carbon $date, Collection $employeeIds): array
    {
        $rows = Attendance::whereDate('date', $date)
            ->whereIn('employee_id', $employeeIds)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $count = fn (array $statuses) => (int) collect($statuses)->sum(fn ($s) => $rows[$s] ?? 0);

        $in = $count(self::IN);
        $leave = $count(self::LEAVE);
        $off = $count(self::OFF);
        $notIn = $count([Attendance::ABSENT]);
        $expected = $in + $notIn;

        return [
            'in' => $in,
            'late' => $count([Attendance::LATE]),
            'not_in' => $notIn,
            'leave' => $leave,
            'off' => $off,
            'expected' => $expected,
            'rate' => $expected > 0 ? round($in / $expected * 100, 1) : null,
        ];
    }

    public function lastDays(int $days, Collection $employeeIds): array
    {
        $from = today()->subDays($days - 1);

        $rows = Attendance::whereDate('date', '>=', $from)
            ->whereIn('employee_id', $employeeIds)
            ->selectRaw('date, status, count(*) as total')
            ->groupBy('date', 'status')
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $series = ['labels' => [], 'dates' => [], 'present' => [], 'late' => [], 'leave' => [], 'absent' => []];
        for ($date = $from->copy(); $date->lte(today()); $date->addDay()) {
            $day = $rows[$date->toDateString()] ?? collect();
            $series['labels'][] = $date->format('d M');
            $series['dates'][] = $date->format('D, d M');
            $series['present'][] = (int) $day->whereIn('status', [Attendance::PRESENT, Attendance::HALF_DAY])->sum('total');
            $series['late'][] = (int) $day->where('status', Attendance::LATE)->sum('total');
            $series['leave'][] = (int) $day->whereIn('status', self::LEAVE)->sum('total');
            $series['absent'][] = (int) $day->where('status', Attendance::ABSENT)->sum('total');
        }

        return $series;
    }

    public function departments(Collection $employeeIds): Collection
    {
        $inToday = Attendance::whereDate('date', today())
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('status', self::IN)
            ->pluck('employee_id')
            ->flip();

        return Employee::whereIn('id', $employeeIds)
            ->get(['id', 'department_id'])
            ->groupBy('department_id')
            ->map(fn ($group, $departmentId) => [
                'name' => $departmentId ? (Department::find($departmentId)?->name ?? 'Unknown') : 'No department',
                'total' => $group->count(),
                'in' => $group->filter(fn ($e) => isset($inToday[$e->id]))->count(),
            ])
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    public function payrollTrend(?int $branchId): array
    {
        $from = today()->startOfMonth()->subMonths(5);

        $runs = PayrollRun::where('status', '!=', PayrollRun::DRAFT)
            ->whereDate('month', '>=', $from)
            ->when($branchId, fn ($q) => $q->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branchId)))
            ->get()
            ->groupBy(fn ($run) => $run->month->format('Y-m'));

        $labels = $values = [];
        for ($month = $from->copy(); $month->lte(today()); $month->addMonth()) {
            $labels[] = $month->format('M Y');
            $values[] = round((float) ($runs[$month->format('Y-m')] ?? collect())->sum('total_net'), 2);
        }

        return compact('labels', 'values');
    }

    public function lateLeaders(Collection $employeeIds): Collection
    {
        return Attendance::with('employee')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [today()->startOfMonth()->toDateString(), today()->toDateString()])
            ->where('late_minutes', '>', 0)
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($rows) => [
                'employee' => $rows->first()->employee,
                'count' => $rows->count(),
                'minutes' => (int) $rows->sum('late_minutes'),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();
    }

    public function birthdays(Collection $employees): Collection
    {
        return $employees
            ->map(function (Employee $employee) {
                $next = $employee->date_of_birth->copy()->year(today()->year);
                if ($next->lt(today())) {
                    $next->addYear();
                }

                return ['employee' => $employee, 'date' => $next, 'days' => (int) today()->diffInDays($next)];
            })
            ->filter(fn ($row) => $row['days'] <= 30)
            ->sortBy('days')
            ->take(5)
            ->values();
    }
}
