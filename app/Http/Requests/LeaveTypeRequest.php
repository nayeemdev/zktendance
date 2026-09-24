<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:10', Rule::unique('leave_types')->ignore($this->route('leave_type'))],
            'days_per_year' => ['required', 'numeric', 'min:0', 'max:365'],
            'accrual' => ['required', 'in:yearly,monthly'],
            'gender' => ['nullable', 'in:male,female'],
            'is_encashable' => ['boolean'],
            'carry_forward_limit' => ['required', 'numeric', 'min:0', 'max:365'],
            'is_paid' => ['boolean'],
            'allow_half_day' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_paid' => $this->boolean('is_paid'),
            'allow_half_day' => $this->boolean('allow_half_day'),
            'is_active' => $this->boolean('is_active'),
            'is_encashable' => $this->boolean('is_encashable'),
            'gender' => $this->input('gender') ?: null,
        ]);
    }
}
