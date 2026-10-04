<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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
            'title' => ['required', 'string', 'max:255',
                Rule::unique('promotions', 'title')->ignore($this->route('promotion'))],
            'slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('promotions')->ignore($this->route('promotion'))],
            'body' => ['required', 'string', 'max:100000'], 'media_id' => ['nullable', 'exists:media,id'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['required', 'boolean'], 'vehicles' => ['array', 'max:30'],
            'vehicles.*' => ['integer', 'distinct', 'exists:vehicles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Tên khuyến mãi đã tồn tại. Vui lòng chọn tên khác.',
            'slug.unique' => 'Đường dẫn đã được sử dụng. Vui lòng chọn đường dẫn khác.',
            'slug.regex' => 'Đường dẫn chỉ gồm chữ thường không dấu, số và gạch ngang giữa các từ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $source = $this->input('title');
        if (is_string($source)) {
            $this->merge(['title' => trim($source)]);
            if (blank($this->input('slug'))) {
                $slug = Str::slug(trim($source));
                $this->merge(['slug' => rtrim(Str::substr($slug, 0, 180), '-')]);
            }
        }
    }
}
