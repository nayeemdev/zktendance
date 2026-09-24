<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OvertimeRuleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['nullable', 'exists:branches,id', Rule::unique('overtime_rules')->ignore($this->route('overtime_rule'))],
            'is_enabled' => ['boolean'],
            'requires_approval' => ['boolean'],
            'min_minutes' => ['required', 'integer', 'min:0', 'max:600'],
            'rounding_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'max_daily_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'rate_base' => ['required', 'in:basic,gross'],
            'monthly_hours_divisor' => ['required', 'integer', 'min:1', 'max:744'],
            'workday_multiplier' => ['required', 'numeric', 'min:0', 'max:10'],
            'offday_multiplier' => ['required', 'numeric', 'min:0', 'max:10'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_enabled' => $this->boolean('is_enabled'), 'requires_approval' => $this->boolean('requires_approval'), 'branch_id' => $this->branch_id ?: null]);
    }
}
