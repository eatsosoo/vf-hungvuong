<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $this->user()->can($post ? 'update' : 'create', $post ?? Post::class)
            && ($this->input('status') === 'draft' || $this->user()->can('publish', Post::class));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255',
                Rule::unique('posts', 'title')->ignore($this->route('post'))],
            'slug' => ['required', 'string', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('posts')->ignore($this->route('post')),
                Rule::unique('post_redirects', 'slug')],
            'excerpt' => ['nullable', 'string', 'max:1000'], 'body' => ['required', 'string', 'max:100000'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published'])],
            'published_at' => ['nullable', 'required_if:status,scheduled', 'date', 'after:now'],
            'category_id' => ['nullable', 'exists:categories,id'], 'media_id' => ['nullable', 'exists:media,id'],
            'share_media_id' => ['nullable', 'exists:media,id'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:120'],
            'canonical_url' => ['nullable',
                'url:http,https',
                'max:255',
                function (string $attribute,
                    mixed $value,
                    \Closure $fail): void {
                    if (parse_url($value, PHP_URL_HOST) !== parse_url(config('app.url'), PHP_URL_HOST)) {
                        $fail('Canonical phải thuộc tên miền website.');
                    }
                }],
            'tags' => ['array', 'max:30'], 'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
            'vehicles' => ['array', 'max:30'], 'vehicles.*' => ['integer', 'distinct', 'exists:vehicles,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Tiêu đề bài viết đã tồn tại. Vui lòng chọn tên khác.',
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
