<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'early_leave_grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'half_day_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'is_default' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
        ]);
    }
}
