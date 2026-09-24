<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftAssignment extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => DateOnly::class,
            'end_date' => DateOnly::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function scopeCovering(Builder $query, \DateTimeInterface $from, ?\DateTimeInterface $to = null): Builder
    {
        return $query->whereDate('start_date', '<=', $to ?? $from)->whereDate('end_date', '>=', $from);
    }

    public function auditLabel(): string
    {
        return 'Shift of '.($this->employee?->name ?? '#'.$this->employee_id);
    }
}
