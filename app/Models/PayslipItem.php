<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function shouldAudit(): bool
    {
        return auth()->check() && $this->payslip?->edited_at !== null;
    }

    public function auditLabel(): string
    {
        return $this->name.' on payslip of '.($this->payslip?->employee?->name ?? '#'.$this->payslip_id);
    }
}
