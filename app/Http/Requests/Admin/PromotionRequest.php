<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-catalog');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('promotions')->ignore($this->route('promotion'))],
            'body' => ['required', 'string', 'max:100000'], 'media_id' => ['nullable', 'exists:media,id'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'], 'vehicles' => ['array', 'max:30'],
            'vehicles.*' => ['integer', 'distinct', 'exists:vehicles,id'],
        ];
    }
}
