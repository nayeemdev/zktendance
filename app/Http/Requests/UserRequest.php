<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->route('user'))],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_MANAGER])],
            'branch_id' => ['nullable', 'required_if:role,manager', 'exists:branches,id'],
            'is_active' => ['boolean'],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'branch_id' => $this->input('role') === User::ROLE_MANAGER ? $this->input('branch_id') : null,
        ]);
    }
}
