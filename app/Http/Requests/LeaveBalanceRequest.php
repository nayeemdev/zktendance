<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveBalanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'allocated' => ['required', 'numeric', 'min:0'],
            'carried_forward' => ['required', 'numeric', 'min:0'],
        ];
    }
}
