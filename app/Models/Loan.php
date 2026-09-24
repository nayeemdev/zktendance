<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected $attributes = ['status' => 'active', 'paid_amount' => 0];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'installment' => 'float',
            'paid_amount' => 'float',
            'start_month' => DateOnly::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function remaining(): float
    {
        return round($this->amount - $this->paid_amount, 2);
    }

    public function auditLabel(): string
    {
        return 'Loan of '.($this->employee?->name ?? '#'.$this->employee_id);
    }
}
