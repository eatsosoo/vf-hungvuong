<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
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

        return ['name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique($table)->ignore($this->route('taxonomy'))]];
    }
}
