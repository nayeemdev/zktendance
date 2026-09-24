<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'expires_on' => DateOnly::class,
            'visible_to_employee' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_on?->isPast() ?? false;
    }

    public function expiresSoon(): bool
    {
        return $this->expires_on !== null && ! $this->isExpired() && $this->expires_on->lte(today()->addDays(30));
    }

    public function auditLabel(): string
    {
        return $this->title.' of '.($this->employee?->name ?? '#'.$this->employee_id);
    }
}
