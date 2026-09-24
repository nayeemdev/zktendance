<?php

namespace App\Http\Requests;

use App\Services\SetupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_email' => ['nullable', 'email'],
            'country' => ['required', Rule::in(array_keys(SetupService::COUNTRIES))],
            'currency' => ['required', 'string', 'max:5'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'timezone'],
            'branch_name' => ['required', 'string', 'max:100'],
            'office_start' => ['required', 'date_format:H:i'],
            'office_end' => ['required', 'date_format:H:i'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
