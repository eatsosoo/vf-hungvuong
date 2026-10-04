<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-content');
    }

    public function rules(): array
    {
        $imageRules = ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            'dimensions:max_width=6000,max_height=6000'];

        return [
            'image' => ['required_without:images', 'prohibits:images', ...$imageRules],
            'alt' => ['nullable', 'string', 'max:255'],
            'images' => ['required_without:image', 'prohibits:image', 'array', 'list', 'min:1', 'max:20'],
            'images.*' => ['required', ...$imageRules],
            'alts' => ['nullable', 'array', 'list', 'max:20'],
            'alts.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
