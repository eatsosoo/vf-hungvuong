<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-catalog');
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255',
            Rule::unique('pages', 'title')->ignore($this->route('page'))],
            'slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('pages')->ignore($this->route('page'))],
            'body' => ['required', 'string', 'max:100000'], 'is_active' => ['required', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Tiêu đề trang đã tồn tại. Vui lòng chọn tên khác.',
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
