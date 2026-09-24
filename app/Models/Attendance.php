<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable;

    public const PRESENT = 'present';

    public const LATE = 'late';

    public const HALF_DAY = 'half_day';

    public const ABSENT = 'absent';

    public const LEAVE = 'leave';

    public const UNPAID_LEAVE = 'unpaid_leave';

    public const HOLIDAY = 'holiday';

    public const WEEKEND = 'weekend';

    public const STATUSES = [
        self::PRESENT => 'Present',
        self::LATE => 'Late',
        self::HALF_DAY => 'Half Day',
        self::ABSENT => 'Absent',
        self::LEAVE => 'On Leave',
        self::UNPAID_LEAVE => 'Unpaid Leave',
        self::HOLIDAY => 'Holiday',
        self::WEEKEND => 'Weekend',
    ];

    public const COLORS = [
        self::PRESENT => 'success',
        self::LATE => 'warning',
        self::HALF_DAY => 'info',
        self::ABSENT => 'danger',
        self::LEAVE => 'primary',
        self::UNPAID_LEAVE => 'secondary',
        self::HOLIDAY => 'dark',
        self::WEEKEND => 'light',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => DateOnly::class,
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'is_manual' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overtime_reviewed_by');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::COLORS[$this->status] ?? 'secondary';
    }

    public function shouldAudit(): bool
    {
        return $this->is_manual || $this->getOriginal('is_manual');
    }

    public function auditLabel(): string
    {
        return ($this->employee?->name ?? 'Employee').' on '.$this->date?->toDateString();
    }
}
