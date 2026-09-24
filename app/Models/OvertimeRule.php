<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'workday_multiplier' => 'float',
            'offday_multiplier' => 'float',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function forBranch(?int $branchId): ?self
    {
        return static::where('branch_id', $branchId)->first() ?? static::whereNull('branch_id')->first();
    }

    public function applyRounding(int $minutes): int
    {
        if ($minutes < $this->min_minutes) {
            return 0;
        }

        if ($this->rounding_minutes > 0) {
            $minutes = intdiv($minutes, $this->rounding_minutes) * $this->rounding_minutes;
        }

        return $this->max_daily_minutes > 0 ? min($minutes, $this->max_daily_minutes) : $minutes;
    }
}
