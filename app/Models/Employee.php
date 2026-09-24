<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use Auditable, HasFactory;

    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'resigned' => 'Resigned', 'terminated' => 'Terminated'];

    public const EMPLOYMENT_TYPES = ['permanent' => 'Permanent', 'probation' => 'Probation', 'contract' => 'Contract', 'intern' => 'Intern'];

    public const GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => DateOnly::class,
            'joining_date' => DateOnly::class,
            'leaving_date' => DateOnly::class,
            'tax_enabled' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class)->latest('effective_from');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class)->ofMany(['effective_from' => 'max', 'id' => 'max']);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function salaryOn(\DateTimeInterface $date): ?EmployeeSalary
    {
        return $this->salaries()
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }
}
