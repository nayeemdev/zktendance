<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeSalaryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'salary_structure_id' => ['required', 'exists:salary_structures,id'],
            'gross_salary' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
