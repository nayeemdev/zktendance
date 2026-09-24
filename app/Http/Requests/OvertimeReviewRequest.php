<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OvertimeReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'decision' => ['required', 'in:approved,rejected'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'Select at least one overtime entry.'];
    }
}
