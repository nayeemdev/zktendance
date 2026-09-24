<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'expires_on' => ['nullable', 'date'],
            'visible_to_employee' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['visible_to_employee' => $this->boolean('visible_to_employee')]);
    }
}
