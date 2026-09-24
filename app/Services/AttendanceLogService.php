<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Support\Carbon;

class AttendanceLogService
{
    public function __construct(private AttendanceService $attendance) {}

    /**
     * @param  array<int, array{user_id: string, timestamp: string, state?: int|string|null, type?: int|string|null}>  $records
     */
    public function store(?Device $device, array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $employees = Employee::whereNotNull('device_user_id')->pluck('id', 'device_user_id');
        $now = now();
        $rows = [];

        foreach ($records as $record) {
            $userId = trim((string) $record['user_id']);
            if ($userId === '' || empty($record['timestamp'])) {
                continue;
            }

            $rows[] = [
                'device_id' => $device?->id,
                'employee_id' => $employees[$userId] ?? null,
                'device_user_id' => $userId,
                'punched_at' => Carbon::parse($record['timestamp'])->format('Y-m-d H:i:s'),
                'verify_type' => AttendanceLog::VERIFY_TYPES[(int) ($record['type'] ?? -1)] ?? (string) ($record['type'] ?? ''),
                'state' => (string) ($record['state'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $inserted = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            $inserted += AttendanceLog::insertOrIgnore($chunk);
        }

        if ($inserted > 0) {
            $this->reprocess($rows);
        }

        return $inserted;
    }

    public function linkEmployee(Employee $employee): void
    {
        if (! $employee->device_user_id) {
            return;
        }

        AttendanceLog::where('device_user_id', $employee->device_user_id)
            ->where(fn ($q) => $q->whereNull('employee_id')->orWhere('employee_id', '!=', $employee->id))
            ->update(['employee_id' => $employee->id]);
    }

    private function reprocess(array $rows): void
    {
        $employeeIds = collect($rows)->pluck('employee_id')->filter()->unique()->values();
        if ($employeeIds->isEmpty()) {
            return;
        }

        $dates = collect($rows)->pluck('punched_at');
        $from = Carbon::parse($dates->min())->subDay()->startOfDay();
        $to = Carbon::parse($dates->max())->startOfDay();

        $this->attendance->processRange($from, $to, $employeeIds->all());
    }
}
