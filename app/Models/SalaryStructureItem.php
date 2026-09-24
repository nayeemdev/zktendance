<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStructureItem extends Model
{
    public const CALCULATIONS = [
        'percent_of_gross' => '% of Gross',
        'percent_of_basic' => '% of Basic',
        'fixed' => 'Fixed Amount',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['value' => 'float'];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}
