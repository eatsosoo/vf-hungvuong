<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-catalog');
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('pages')->ignore($this->route('page'))],
            'body' => ['required', 'string', 'max:100000'], 'is_active' => ['required', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:500']];
    }
}
