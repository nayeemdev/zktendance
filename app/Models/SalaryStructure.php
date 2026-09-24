<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryStructure extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    public function items(): HasMany
    {
        return $this->hasMany(SalaryStructureItem::class);
    }
}
