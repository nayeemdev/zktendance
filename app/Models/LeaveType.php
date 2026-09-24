<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'days_per_year' => 'float',
            'carry_forward_limit' => 'float',
            'is_paid' => 'boolean',
            'allow_half_day' => 'boolean',
            'is_active' => 'boolean',
            'is_encashable' => 'boolean',
        ];
    }
}
