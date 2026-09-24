<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveApplyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => [$this->routeIs('admin.*') ? 'required' : 'prohibited', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required_unless:is_half_day,1', 'nullable', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_half_day' => $this->boolean('is_half_day')]);
    }
}
