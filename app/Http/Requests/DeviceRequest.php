<?php

namespace App\Http\Requests;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'branch_id' => ['required', 'exists:branches,id'],
            'model' => ['nullable', Rule::in(array_keys(Device::MODELS))],
            'connection_mode' => ['required', Rule::in(array_keys(Device::MODES))],
            'serial_number' => ['nullable', 'required_if:connection_mode,push', 'string', 'max:50', Rule::unique('devices')->ignore($this->route('device'))],
            'ip_address' => ['nullable', 'required_if:connection_mode,pull', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function messages(): array
    {
        return [
            'serial_number.required_if' => 'Serial number is required for push mode so the device can be recognised.',
            'ip_address.required_if' => 'IP address is required for pull mode.',
        ];
    }
}
