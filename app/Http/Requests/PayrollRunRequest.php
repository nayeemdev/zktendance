<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayrollRunRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }
}
