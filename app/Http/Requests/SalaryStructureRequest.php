<?php

namespace App\Http\Requests;

use App\Models\SalaryStructureItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryStructureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array'],
            'items.*.enabled' => ['nullable', 'boolean'],
            'items.*.calculation' => ['required', Rule::in(array_keys(SalaryStructureItem::CALCULATIONS))],
            'items.*.value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
