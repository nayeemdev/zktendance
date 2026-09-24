<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'installment' => ['required', 'numeric', 'min:1', 'lte:amount'],
            'paid_amount' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
            'start_month' => ['required', 'date'],
            'status' => ['required', 'in:active,paused,completed'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (preg_match('/^\d{4}-\d{2}$/', (string) $this->start_month)) {
            $this->merge(['start_month' => $this->start_month.'-01']);
        }

        $this->merge(['paid_amount' => $this->paid_amount ?: 0]);
    }
}
