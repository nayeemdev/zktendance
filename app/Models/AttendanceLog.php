<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    public const VERIFY_TYPES = [0 => 'Password', 1 => 'Fingerprint', 2 => 'Card', 4 => 'Card', 9 => 'Password', 15 => 'Face', 25 => 'Palm'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['punched_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
