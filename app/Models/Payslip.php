<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payable_days' => 'float',
            'present_days' => 'float',
            'absent_days' => 'float',
            'paid_leave_days' => 'float',
            'unpaid_leave_days' => 'float',
            'overtime_hours' => 'float',
            'gross_salary' => 'float',
            'total_earnings' => 'float',
            'total_deductions' => 'float',
            'net_salary' => 'float',
            'emailed_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public function earnings()
    {
        return $this->items->where('type', SalaryComponent::EARNING);
    }

    public function deductions()
    {
        return $this->items->where('type', SalaryComponent::DEDUCTION);
    }
}
