<?php

namespace App\Services;

use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CorrectionService
{
    public function __construct(
        private AttendanceService $attendance,
        private NotificationService $notifications,
    ) {}

    public function request(Employee $employee, array $data): AttendanceCorrection
    {
        $correction = $employee->corrections()->create($data);

        $this->notifications->staff(
            'New attendance correction',
            "{$employee->name} asked to correct attendance for {$correction->date->format('d M Y')}.",
            route('admin.corrections.index')
        );

        return $correction;
    }

    public function approve(AttendanceCorrection $correction, User $reviewer): void
    {
        $this->ensurePending($correction);

        $this->attendance->saveManual(
            $correction->employee,
            $correction->date,
            $correction->check_in ? substr($correction->check_in, 0, 5) : null,
            $correction->check_out ? substr($correction->check_out, 0, 5) : null,
            null,
            'Correction: '.$correction->reason,
        );

        $this->close($correction, 'approved', $reviewer);
    }

    public function reject(AttendanceCorrection $correction, User $reviewer): void
    {
        $this->ensurePending($correction);
        $this->close($correction, 'rejected', $reviewer);
    }

    private function close(AttendanceCorrection $correction, string $status, User $reviewer): void
    {
        $correction->update(['status' => $status, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);

        $this->notifications->employee(
            $correction->employee,
            "Attendance correction {$status}",
            "Your correction request for {$correction->date->format('d M Y')} was {$status}.",
            route('portal.corrections.index')
        );
    }

    private function ensurePending(AttendanceCorrection $correction): void
    {
        if ($correction->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request is already reviewed.']);
        }
    }
}
