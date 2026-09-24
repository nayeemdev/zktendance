<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceUserImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['string', 'max:9'],
        ];
    }

    public function messages(): array
    {
        return ['user_ids.required' => 'Select at least one device user to import.'];
    }
}
