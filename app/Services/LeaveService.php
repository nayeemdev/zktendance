<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
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
            ['allocated' => $type->days_per_year, 'carried_forward' => $this->carryForwardFor($employee, $type, $year)]
        );
    }

    public function apply(Employee $employee, array $data): LeaveRequest
    {
        $type = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $halfDay = (bool) ($data['is_half_day'] ?? false);
        $end = $halfDay ? $start->copy() : Carbon::parse($data['end_date']);

        if ($halfDay && ! $type->allow_half_day) {
            throw ValidationException::withMessages(['is_half_day' => 'Half day is not allowed for this leave type.']);
        }

        $days = $this->countDays($employee, $start, $end, $halfDay);
        if ($days <= 0) {
            throw ValidationException::withMessages(['start_date' => 'The selected dates fall on weekends or holidays.']);
        }

        $overlap = $employee->leaveRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'You already have a leave request for these dates.']);
        }

        $this->ensureBalance($employee, $type, $start->year, $days);

        $leave = $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => $start,
            'end_date' => $end,
            'is_half_day' => $halfDay,
            'days' => $days,
            'reason' => $data['reason'] ?? null,
        ]);

        $this->notifications->staff(
            'New leave request',
            "{$employee->name} applied for {$days} day(s) of {$type->name} from {$start->format('d M Y')}.",
            route('admin.leaves.index')
        );

        return $leave;
    }

    public function approve(LeaveRequest $request, User $reviewer, ?string $note = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $note) {
            $this->ensureBalance($request->employee, $request->leaveType, $request->start_date->year, $request->days);

            $request->update([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $this->balance($request->employee, $request->leaveType, $request->start_date->year)->increment('used', $request->days);
        });

        $this->attendance->processRange($request->start_date, $request->end_date, [$request->employee_id]);
        $this->notifyEmployee($request, 'approved');
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
                $this->balance($employee, $type, $year);
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
                $this->balance($request->employee, $request->leaveType, $request->start_date->year)->decrement('used', $request->days);
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

    private function ensureBalance(Employee $employee, LeaveType $type, int $year, float $days): void
    {
        if (! $type->is_paid || $type->days_per_year <= 0) {
            return;
        }

        $remaining = $this->balance($employee, $type, $year)->remaining();
        if ($days > $remaining) {
            throw ValidationException::withMessages(['leave_type_id' => "Not enough {$type->name} balance. Remaining: {$remaining} day(s)."]);
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
