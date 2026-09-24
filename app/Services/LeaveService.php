<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveEncashment;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollAdjustment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private AttendanceService $attendance,
        private NotificationService $notifications,
    ) {}

    public function countDays(Employee $employee, Carbon $start, Carbon $end, bool $halfDay = false): float
    {
        $holidays = Holiday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $employee->branch_id))
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $days = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (! isset($holidays[$date->toDateString()]) && ! $employee->branch?->isWeekend($date)) {
                $days++;
            }
        }

        return $halfDay ? min($days, 1) * 0.5 : $days;
    }

    public function balance(Employee $employee, LeaveType $type, int $year): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
            ['allocated' => $this->allocationFor($employee, $type, $year), 'carried_forward' => $this->carryForwardFor($employee, $type, $year)]
        );
    }

    public function allocationFor(Employee $employee, LeaveType $type, int $year): float
    {
        if ($type->accrual !== 'monthly') {
            return $type->days_per_year;
        }

        $from = Carbon::create($year)->startOfYear()->max($employee->joining_date->copy()->startOfMonth());
        $to = Carbon::create($year)->endOfYear()->min(today());
        $months = $from->lte($to) ? $from->diffInMonths($to->copy()->startOfMonth()) + 1 : 0;

        return round($type->days_per_year / 12 * $months, 1);
    }

    public function eligibleTypes(Employee $employee)
    {
        return LeaveType::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('gender')->orWhere('gender', $employee->gender))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, float> days of the leave that fall in each year
     */
    public function yearPortions(Employee $employee, Carbon $start, Carbon $end, bool $halfDay = false): array
    {
        if ($start->year === $end->year) {
            return [$start->year => $this->countDays($employee, $start, $end, $halfDay)];
        }

        return [
            $start->year => $this->countDays($employee, $start, $start->copy()->endOfYear()->startOfDay()),
            $end->year => $this->countDays($employee, $end->copy()->startOfYear(), $end),
        ];
    }

    public function encash(Employee $employee, LeaveType $type, int $year, float $days, string $month, User $user): LeaveEncashment
    {
        if (! $type->is_encashable) {
            throw ValidationException::withMessages(['leave_type_id' => "{$type->name} cannot be encashed."]);
        }

        $balance = $this->balance($employee, $type, $year);
        if ($days > $balance->remaining()) {
            throw ValidationException::withMessages(['days' => "Only {$balance->remaining()} day(s) are available."]);
        }

        $salary = $employee->salaryOn(today());
        if (! $salary) {
            throw ValidationException::withMessages(['employee_id' => 'This employee has no salary set.']);
        }

        $breakdown = app(SalaryService::class)->breakdown($salary->structure, $salary->gross_salary);
        $base = setting('encashment_base') === 'gross' ? $salary->gross_salary : app(SalaryService::class)->basic($breakdown);
        $amount = round($base / 30 * $days, 2);

        return DB::transaction(function () use ($employee, $type, $year, $days, $month, $user, $balance, $amount) {
            $adjustment = PayrollAdjustment::create([
                'employee_id' => $employee->id,
                'month' => $month.'-01',
                'type' => 'earning',
                'title' => "Leave Encashment: {$type->name} ({$days} days)",
                'amount' => $amount,
            ]);

            $balance->increment('encashed', $days);

            return LeaveEncashment::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'payroll_adjustment_id' => $adjustment->id,
                'year' => $year,
                'days' => $days,
                'amount' => $amount,
                'created_by' => $user->id,
            ]);
        });
    }

    public function removeEncashment(LeaveEncashment $encashment): void
    {
        DB::transaction(function () use ($encashment) {
            $this->balance($encashment->employee, $encashment->leaveType, $encashment->year)->decrement('encashed', $encashment->days);
            $encashment->adjustment?->delete();
            $encashment->delete();
        });
    }

    public function apply(Employee $employee, array $data): LeaveRequest
    {
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $halfDay = (bool) ($data['is_half_day'] ?? false);
        $end = $halfDay ? $start->copy() : Carbon::parse($data['end_date']);

        if ($type->gender && $type->gender !== $employee->gender) {
            throw ValidationException::withMessages(['leave_type_id' => "{$type->name} is only for ".($type->gender === 'female' ? 'female' : 'male').' employees.']);
        }

        if ($halfDay && ! $type->allow_half_day) {
            throw ValidationException::withMessages(['is_half_day' => 'Half day is not allowed for this leave type.']);
        }

        $days = $this->countDays($employee, $start, $end, $halfDay);
        if ($days <= 0) {
            throw ValidationException::withMessages(['start_date' => 'The selected dates fall on weekends or holidays.']);
        }

        $overlap = $employee->leaveRequests()
            ->whereIn('status', ['pending', 'recommended', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'You already have a leave request for these dates.']);
        }

        $this->ensureBalance($employee, $type, $this->yearPortions($employee, $start, $end, $halfDay));

        $leave = $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => $start,
            'end_date' => $end,
            'is_half_day' => $halfDay,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
        ]);

        $message = "{$employee->name} applied for {$days} day(s) of {$type->name} from {$start->format('d M Y')}.";
        $managers = $this->notifications->branchManagers($employee->branch_id, 'New leave request', $message, route('admin.leaves.index'));

        if (! $this->twoLevel() || $managers === 0) {
            $this->notifications->staff('New leave request', $message, route('admin.leaves.index'));
        }

        return $leave;
    }

    public function approve(LeaveRequest $request, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $note) {
            $portions = $this->portionsOf($request);
            $this->ensureBalance($request->employee, $request->leaveType, $portions);

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            foreach ($portions as $year => $days) {
                $this->balance($request->employee, $request->leaveType, $year)->increment('used', $days);
            }
        });

        $this->attendance->processRange($request->start_date, $request->end_date, [$request->employee_id]);
        $this->notifyEmployee($request, 'approved');
    }

    public function recommend(LeaveRequest $request, User $manager, ?string $note = null): void
    {
        $request->update([
            'status' => 'recommended',
            'recommended_by' => $manager->id,
            'recommended_at' => now(),
            'review_note' => $note,
        ]);

        $this->notifications->staff(
            'Leave recommended',
            "{$manager->name} recommended {$request->employee->name}'s {$request->leaveType->name} from {$request->start_date->format('d M Y')}. Final approval is needed.",
            route('admin.leaves.index', ['status' => 'recommended'])
        );
    }

    public function canApprove(User $user, LeaveRequest $request): bool
    {
        if (! $request->isOpen() || ! $user->canManageEmployee($request->employee)) {
            return false;
        }

        return $user->isStaff() || ! $this->twoLevel();
    }

    public function canRecommend(User $user, LeaveRequest $request): bool
    {
        return $this->twoLevel() && $user->isManager() && $request->status === 'pending' && $user->canManageEmployee($request->employee);
    }

    public function canReject(User $user, LeaveRequest $request): bool
    {
        return $request->isOpen() && $user->canManageEmployee($request->employee);
    }

    public function twoLevel(): bool
    {
        return (int) setting('leave_approval_levels', 1) === 2;
    }

    public function reject(LeaveRequest $request, User $reviewer, ?string $note = null): void
    {
        $this->close($request, 'rejected', $reviewer, $note);
        $this->notifyEmployee($request, 'rejected');
    }

    public function cancel(LeaveRequest $request, ?User $user = null): void
    {
        $this->close($request, 'cancelled', $user, null);
    }

    public function allocateYear(int $year, ?Employee $only = null): int
    {
        $count = 0;
        $types = LeaveType::where('is_active', true)->get();
        $employees = $only ? collect([$only]) : Employee::active()->get();

        $employees->each(function (Employee $employee) use ($types, $year, &$count) {
            foreach ($types as $type) {
                if ($type->gender && $type->gender !== $employee->gender) {
                    continue;
                }

                $balance = $this->balance($employee, $type, $year);
                if ($type->accrual === 'monthly' && ! $balance->wasRecentlyCreated) {
                    $balance->update(['allocated' => $this->allocationFor($employee, $type, $year)]);
                }
                $count++;
            }
        });

        return $count;
    }

    private function close(LeaveRequest $request, string $status, ?User $user, ?string $note): void
    {
        $wasApproved = $request->status === 'approved';

        DB::transaction(function () use ($request, $status, $user, $note, $wasApproved) {
            if ($wasApproved) {
                foreach ($this->portionsOf($request) as $year => $days) {
                    $this->balance($request->employee, $request->leaveType, $year)->decrement('used', $days);
                }
            }

            $request->update([
                'status' => $status,
                'reviewed_by' => $user?->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);
        });

        if ($wasApproved) {
            $this->attendance->processRange($request->start_date, $request->end_date, [$request->employee_id]);
        }
    }

    private function notifyEmployee(LeaveRequest $request, string $status): void
    {
        $note = $request->review_note ? " Note: {$request->review_note}" : '';

        $this->notifications->employee(
            $request->employee,
            "Leave {$status}",
            "Your {$request->leaveType->name} from {$request->start_date->format('d M Y')} to {$request->end_date->format('d M Y')} was {$status}.{$note}",
            route('portal.leaves.index')
        );
    }

    private function portionsOf(LeaveRequest $request): array
    {
        return $this->yearPortions($request->employee, $request->start_date, $request->end_date, $request->is_half_day);
    }

    private function ensureBalance(Employee $employee, LeaveType $type, array $portions): void
    {
        if (! $type->is_paid || $type->days_per_year <= 0) {
            return;
        }

        foreach ($portions as $year => $days) {
            $remaining = $this->balance($employee, $type, $year)->remaining();
            if ($days > $remaining) {
                throw ValidationException::withMessages(['leave_type_id' => "Not enough {$type->name} balance for {$year}. Remaining: {$remaining} day(s)."]);
            }
        }
    }

    private function carryForwardFor(Employee $employee, LeaveType $type, int $year): float
    {
        if ($type->carry_forward_limit <= 0) {
            return 0;
        }

        $previous = LeaveBalance::where(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year - 1])->first();

        return $previous ? max(0, min($previous->remaining(), $type->carry_forward_limit)) : 0;
    }
}
