<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeviceUserLinkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'max:9'],
            'employee_id' => ['required', 'exists:employees,id'],
        ];
    }
}
