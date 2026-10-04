<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-content');
    }

    public function rules(): array
    {
        $table = $this->route('kind') === 'categories' ? 'categories' : 'tags';

        return ['name' => ['required', 'string', 'max:100',
            Rule::unique($table, 'name')->ignore($this->route('taxonomy'))],
            'slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique($table)->ignore($this->route('taxonomy'))]];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Tên phân loại đã tồn tại. Vui lòng chọn tên khác.',
            'slug.unique' => 'Đường dẫn đã được sử dụng. Vui lòng chọn đường dẫn khác.',
            'slug.regex' => 'Đường dẫn chỉ gồm chữ thường không dấu, số và gạch ngang giữa các từ.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $source = $this->input('name');
        if (is_string($source)) {
            $this->merge(['name' => trim($source)]);
            if (blank($this->input('slug'))) {
                $slug = Str::slug(trim($source));
                $this->merge(['slug' => rtrim(Str::substr($slug, 0, 180), '-')]);
            }
        }
    }
}
