<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees')->ignore($employee)],
            'device_user_id' => ['nullable', 'string', 'max:9', Rule::unique('employees')->ignore($employee)],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'required_if:create_login,1', 'email', Rule::unique('users', 'email')->ignore($employee?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::in(array_keys(Employee::GENDERS))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'branch_id' => ['required', 'exists:branches,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'joining_date' => ['required', 'date'],
            'leaving_date' => ['nullable', 'date', 'after_or_equal:joining_date'],
            'employment_type' => ['required', Rule::in(array_keys(Employee::EMPLOYMENT_TYPES))],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'nid' => ['nullable', 'string', 'max:30'],
            'tin' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_no' => ['nullable', 'string', 'max:50'],
            'tax_enabled' => ['boolean'],
            'create_login' => ['boolean'],
            'login_role' => ['nullable', 'in:employee,manager'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_enabled' => $this->boolean('tax_enabled'),
            'create_login' => $this->boolean('create_login'),
        ]);
    }

    public function messages(): array
    {
        return ['email.required_if' => 'Email is required to create a login account.'];
    }
}
