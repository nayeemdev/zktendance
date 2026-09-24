<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalaryComponentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:earning,deduction'],
            'is_basic' => ['boolean'],
            'is_taxable' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_basic' => $this->boolean('is_basic') && $this->input('type') === 'earning',
            'is_taxable' => $this->boolean('is_taxable'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }
}
