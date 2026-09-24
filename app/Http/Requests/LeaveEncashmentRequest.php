<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveEncashmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'month' => ['required', 'date_format:Y-m'],
        ];
    }
}
