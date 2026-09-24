<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return $this->user()->employee
            ? [
                'phone' => ['nullable', 'string', 'max:30'],
                'address' => ['nullable', 'string', 'max:500'],
            ]
            : [
                'name' => ['required', 'string', 'max:100'],
            ];
    }
}
