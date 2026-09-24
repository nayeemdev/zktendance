<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use Auditable;

    public const DRAFT = 'draft';

    public const APPROVED = 'approved';

    public const PAID = 'paid';

    public const STATUS_COLORS = [self::DRAFT => 'secondary', self::APPROVED => 'primary', self::PAID => 'success'];

    protected $guarded = ['id'];

    protected $attributes = ['status' => self::DRAFT];

    protected function casts(): array
    {
        return [
            'month' => DateOnly::class,
            'total_gross' => 'float',
            'total_deductions' => 'float',
            'total_net' => 'float',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function title(): string
    {
        return $this->month->format('F Y').' - '.($this->branch?->name ?? 'All Branches');
    }

    public function auditLabel(): string
    {
        return 'Payroll '.$this->title();
    }
}
