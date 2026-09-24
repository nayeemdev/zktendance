<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RosterService
{
    public function __construct(private AttendanceService $attendance) {}

    public function employees(array $data): Collection
    {
        return Employee::active()
            ->when($data['employee_ids'] ?? null, fn ($q, $ids) => $q->whereIn('id', $ids))
            ->when($data['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($data['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->get();
    }

    /**
     * Shifts are applied in turn, each for rotate_days, from start_date to end_date.
     */
    public function assign(array $data): int
    {
        $employees = $this->employees($data);
        $shiftIds = array_values($data['shift_ids']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $period = count($shiftIds) > 1 ? max(1, (int) ($data['rotate_days'] ?? 7)) : $start->diffInDays($end) + 1;

        $count = 0;
        DB::transaction(function () use ($employees, $shiftIds, $start, $end, $period, $data, &$count) {
            foreach ($employees as $employee) {
                $index = 0;
                for ($from = $start->copy(); $from->lte($end); $from->addDays($period)) {
                    $to = $from->copy()->addDays($period - 1)->min($end);
                    ShiftAssignment::create([
                        'employee_id' => $employee->id,
                        'shift_id' => $shiftIds[$index++ % count($shiftIds)],
                        'start_date' => $from->toDateString(),
                        'end_date' => $to->toDateString(),
                        'note' => $data['note'] ?? null,
                    ]);
                    $count++;
                }
            }
        });

        if ($start->lte(today())) {
            $this->attendance->processRange($start, $end, $employees->pluck('id')->all());
        }

        return $count;
    }

    public function remove(ShiftAssignment $assignment): void
    {
        $assignment->delete();

        if ($assignment->start_date->lte(today())) {
            $this->attendance->processRange($assignment->start_date, $assignment->end_date, [$assignment->employee_id]);
        }
    }

    public function week(Carbon $start, array $filters): array
    {
        $end = $start->copy()->addDays(6);
        $employees = Employee::with('shift')->active()
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->orderBy('employee_code')
            ->get();

        $assignments = ShiftAssignment::with('shift')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->covering($start, $end)
            ->orderBy('id')
            ->get()
            ->groupBy('employee_id');

        $grid = [];
        foreach ($employees as $employee) {
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $assignment = ($assignments[$employee->id] ?? collect())
                    ->filter(fn ($a) => $a->start_date->lte($day) && $a->end_date->gte($day))
                    ->last();
                $grid[$employee->id][$day->toDateString()] = $assignment?->shift ?? $employee->shift;
            }
        }

        return compact('employees', 'grid', 'end');
    }
}
