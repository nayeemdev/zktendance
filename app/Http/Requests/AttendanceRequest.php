<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => [$this->route('attendance') ? 'nullable' : 'required', 'exists:employees,id'],
            'date' => [$this->route('attendance') ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(array_keys(Attendance::STATUSES))],
            'note' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return ['note.required' => 'Please write a reason for this manual change.'];
    }
}
