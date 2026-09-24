<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayslipItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => [$this->route('item') ? 'prohibited' : 'required', 'in:earning,deduction'],
            'name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
