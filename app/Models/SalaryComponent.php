<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use Auditable;

    public const EARNING = 'earning';

    public const DEDUCTION = 'deduction';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_basic' => 'boolean',
            'is_taxable' => 'boolean',
        ];
    }
}
