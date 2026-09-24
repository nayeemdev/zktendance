<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allocated' => 'float',
            'carried_forward' => 'float',
            'used' => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function remaining(): float
    {
        return $this->allocated + $this->carried_forward - $this->used;
    }

    public function auditLabel(): string
    {
        return 'Leave balance of '.($this->employee?->name ?? '#'.$this->employee_id).' '.$this->year;
    }
}
