<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessAttendanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from' => ['required', 'date', 'before_or_equal:today'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }
}
