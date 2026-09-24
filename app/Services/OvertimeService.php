<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;

class OvertimeService
{
    public function review(array $attendanceIds, string $status, User $reviewer): int
    {
        $rows = Attendance::with('employee')
            ->whereIn('id', $attendanceIds)
            ->whereNotNull('overtime_status')
            ->get()
            ->filter(fn ($row) => $reviewer->canManageEmployee($row->employee));

        foreach ($rows as $row) {
            $row->update(['overtime_status' => $status, 'overtime_reviewed_by' => $reviewer->id]);
        }

        return $rows->count();
    }
}
