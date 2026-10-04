<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-users');
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($this->route('user'))],
            'password' => [$this->route('user') ? 'nullable' : 'required', 'confirmed', 'max:255',
                Password::min(12)->mixedCase()->numbers()->symbols()],
            'role' => ['required', Rule::enum(UserRole::class)], 'is_active' => ['required', 'boolean']];
    }
}
