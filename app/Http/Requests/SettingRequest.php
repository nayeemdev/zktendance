<?php

namespace App\Http\Requests;

use App\Services\SetupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_email' => ['nullable', 'email'],
            'company_logo' => ['nullable', 'image', 'max:1024'],
            'country' => ['required', Rule::in(array_keys(SetupService::COUNTRIES))],
            'currency' => ['required', 'string', 'max:5'],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'timezone' => ['required', 'timezone'],
            'fiscal_year_start_month' => ['required', 'integer', 'between:1,12'],
            'salary_day_basis' => ['required', 'in:calendar,30'],
            'absent_deduction_base' => ['required', 'in:gross,basic'],
            'late_days_per_deduction' => ['required', 'integer', 'min:0', 'max:31'],
            'tax_enabled' => ['boolean'],
            'tax_exempt_fraction' => ['required', 'numeric', 'min:0', 'max:1'],
            'tax_exempt_cap' => ['required', 'numeric', 'min:0'],
            'minimum_tax' => ['required', 'numeric', 'min:0'],
            'payslip_footer' => ['nullable', 'string', 'max:255'],
            'email_payslips' => ['boolean'],
            'email_notifications' => ['boolean'],
            'device_offline_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'slabs' => ['nullable', 'array'],
            'slabs.*.amount' => ['nullable', 'numeric', 'min:0'],
            'slabs.*.rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tax_enabled' => $this->boolean('tax_enabled'),
            'email_payslips' => $this->boolean('email_payslips'),
            'email_notifications' => $this->boolean('email_notifications'),
        ]);
    }
}
