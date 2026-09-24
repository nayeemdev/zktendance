<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSlab extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'rate' => 'float',
        ];
    }
}
