<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaveReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'review_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
