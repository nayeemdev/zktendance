<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\OvertimeRule;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    private ?Shift $defaultShift = null;

    private array $overtimeRules = [];

    public function processRange(Carbon $from, Carbon $to, ?array $employeeIds = null): int
    {
        $to = $to->copy()->min(today());
        $count = 0;

        for ($date = $from->copy()->startOfDay(); $date->lte($to); $date->addDay()) {
            $count += $this->processDate($date->copy(), $employeeIds);
        }

        return $count;
    }

    public function processDate(Carbon $date, ?array $employeeIds = null): int
    {
        $date = $date->copy()->startOfDay();

        $employees = Employee::with(['shift', 'branch'])
            ->where('status', 'active')
            ->whereDate('joining_date', '<=', $date)
            ->where(fn ($q) => $q->whereNull('leaving_date')->orWhereDate('leaving_date', '>=', $date))
            ->when($employeeIds, fn ($q) => $q->whereIn('id', $employeeIds))
            ->get();

        if ($employees->isEmpty()) {
            return 0;
        }

        $rostered = ShiftAssignment::with('shift')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->covering($date)
            ->orderBy('id')
            ->get()
            ->keyBy('employee_id');

        $existing = Attendance::whereDate('date', $date)->whereIn('employee_id', $employees->pluck('id'))->get()->keyBy('employee_id');

        $holidays = Holiday::whereDate('date', $date)->get();

        $leaves = LeaveRequest::with('leaveType')
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get()
            ->keyBy('employee_id');

        $logs = AttendanceLog::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('punched_at', [$date->copy()->subDay(), $date->copy()->addDays(2)])
            ->orderBy('punched_at')
            ->get(['employee_id', 'punched_at'])
            ->groupBy('employee_id');

        $count = 0;
        foreach ($employees as $employee) {
            if ($existing->get($employee->id)?->is_manual) {
                continue;
            }

            $shift = $rostered->get($employee->id)?->shift ?? $employee->shift ?? $this->defaultShift();
            if (! $shift) {
                continue;
            }

            $start = $shift->startsAt($date)->subHours(4);
            $end = $start->copy()->addDay();
            $punches = ($logs[$employee->id] ?? collect())
                ->filter(fn ($log) => $log->punched_at->gte($start) && $log->punched_at->lt($end))
                ->pluck('punched_at');

            $isHoliday = $holidays->contains(fn ($h) => $h->branch_id === null || $h->branch_id === $employee->branch_id);

            $values = $this->calculate($employee, $shift, $date, $punches, [
                'holiday' => $isHoliday,
                'weekend' => $employee->branch?->isWeekend($date) ?? false,
                'leave' => $leaves[$employee->id] ?? null,
            ]);

            $previous = $existing->get($employee->id);
            $values['overtime_status'] = $this->overtimeStatus($employee, $values['overtime_minutes'], $previous);
            if ($values['overtime_status'] === 'pending') {
                $values['overtime_reviewed_by'] = null;
            }

            Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                $values + ['shift_id' => $shift->id, 'is_manual' => false]
            );
            $count++;
        }

        return $count;
    }

    public function saveManual(Employee $employee, Carbon $date, ?string $checkIn, ?string $checkOut, ?string $status, ?string $note): Attendance
    {
        $shift = $employee->shiftOn($date) ?? $this->defaultShift();
        $punches = collect();

        foreach ([$checkIn, $checkOut] as $time) {
            if ($time) {
                $punch = $date->copy()->setTimeFromTimeString($time);
                if ($shift && $punches->isNotEmpty() && $punch->lt($punches->first())) {
                    $punch->addDay();
                }
                $punches->push($punch);
            }
        }

        $values = $shift ? $this->calculate($employee, $shift, $date, $punches, [
            'holiday' => Holiday::whereDate('date', $date)
                ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
                ->exists(),
            'weekend' => $employee->branch?->isWeekend($date) ?? false,
            'leave' => null,
        ]) : ['status' => Attendance::PRESENT];

        if ($status) {
            $values['status'] = $status;
        }

        $needsApproval = ($values['overtime_minutes'] ?? 0) > 0 && $this->overtimeRule($employee->branch_id)?->requires_approval;
        $values['overtime_status'] = $needsApproval ? 'approved' : null;
        $values['overtime_reviewed_by'] = $needsApproval ? auth()->id() : null;

        return Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date->toDateString()],
            $values + ['shift_id' => $shift?->id, 'is_manual' => true, 'note' => $note]
        );
    }

    public function resetToAutomatic(Attendance $attendance): void
    {
        $attendance->update(['is_manual' => false, 'note' => null]);
        $this->processDate($attendance->date, [$attendance->employee_id]);
    }

    /**
     * @param  Collection<int, Carbon>  $punches
     */
    public function calculate(Employee $employee, Shift $shift, Carbon $date, Collection $punches, array $context): array
    {
        $values = [
            'check_in' => null,
            'check_out' => null,
            'worked_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'overtime_minutes' => 0,
            'note' => null,
        ];

        $offDay = $context['holiday'] ? Attendance::HOLIDAY : ($context['weekend'] ? Attendance::WEEKEND : null);
        $leave = $context['leave'];

        if ($punches->isEmpty()) {
            if ($offDay) {
                $values['status'] = $offDay;
            } elseif ($leave) {
                $values['status'] = $leave->leaveType->is_paid ? Attendance::LEAVE : Attendance::UNPAID_LEAVE;
            } else {
                $values['status'] = Attendance::ABSENT;
            }

            return $values;
        }

        $checkIn = $punches->first();
        $checkOut = $punches->count() > 1 && $punches->last()->diffInMinutes($checkIn, true) >= 1 ? $punches->last() : null;

        $values['check_in'] = $checkIn;
        $values['check_out'] = $checkOut;
        $values['worked_minutes'] = $checkOut ? (int) $checkIn->diffInMinutes($checkOut) : 0;

        $rule = $this->overtimeRule($employee->branch_id);

        if ($offDay) {
            $values['status'] = $offDay;
            $values['overtime_minutes'] = $rule ? $rule->applyRounding($values['worked_minutes']) : 0;

            return $values;
        }

        $shiftStart = $shift->startsAt($date);
        $shiftEnd = $shift->endsAt($date);

        $late = $checkIn->gt($shiftStart) ? (int) $shiftStart->diffInMinutes($checkIn) : 0;
        $values['late_minutes'] = $late > $shift->grace_minutes ? $late : 0;

        if ($checkOut && $checkOut->lt($shiftEnd->copy()->subMinutes($shift->early_leave_grace_minutes))) {
            $values['early_leave_minutes'] = (int) $checkOut->diffInMinutes($shiftEnd);
        }

        if ($checkOut && $checkOut->gt($shiftEnd) && $rule) {
            $values['overtime_minutes'] = $rule->applyRounding((int) $shiftEnd->diffInMinutes($checkOut));
        }

        if (! $checkOut) {
            $values['note'] = 'Missing check out';
        }

        if ($checkOut && $values['worked_minutes'] < $shift->half_day_minutes && ! $leave) {
            $values['status'] = Attendance::HALF_DAY;
        } elseif ($values['late_minutes'] > 0) {
            $values['status'] = Attendance::LATE;
        } else {
            $values['status'] = Attendance::PRESENT;
        }

        return $values;
    }

    private function overtimeStatus(Employee $employee, int $minutes, ?Attendance $previous): ?string
    {
        if ($minutes <= 0 || ! $this->overtimeRule($employee->branch_id)?->requires_approval) {
            return null;
        }

        $decided = $previous && in_array($previous->overtime_status, ['approved', 'rejected']) && $previous->overtime_minutes === $minutes;

        return $decided ? $previous->overtime_status : 'pending';
    }

    private function defaultShift(): ?Shift
    {
        return $this->defaultShift ??= Shift::where('is_default', true)->first() ?? Shift::first();
    }

    private function overtimeRule(?int $branchId): ?OvertimeRule
    {
        if (! array_key_exists($branchId ?? 0, $this->overtimeRules)) {
            $rule = OvertimeRule::forBranch($branchId);
            $this->overtimeRules[$branchId ?? 0] = $rule?->is_enabled ? $rule : null;
        }

        return $this->overtimeRules[$branchId ?? 0];
    }
}
