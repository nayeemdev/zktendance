<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'weekend_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function overtimeRule(): HasOne
    {
        return $this->hasOne(OvertimeRule::class);
    }

    public function isWeekend(\DateTimeInterface $date): bool
    {
        return in_array((int) $date->format('w'), $this->weekend_days ?? []);
    }
}
