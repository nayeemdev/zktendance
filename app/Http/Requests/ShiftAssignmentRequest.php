<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftAssignmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:employees,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'shift_ids' => ['required', 'array', 'min:1'],
            'shift_ids.*' => ['required', 'exists:shifts,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'before:'.now()->addYears(2)->toDateString()],
            'rotate_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['shift_ids' => array_values(array_filter((array) $this->input('shift_ids', [])))]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->employee_ids && ! $this->branch_id && ! $this->department_id) {
                $validator->errors()->add('employee_ids', 'Select employees, a branch or a department.');
            }
        });
    }
}
