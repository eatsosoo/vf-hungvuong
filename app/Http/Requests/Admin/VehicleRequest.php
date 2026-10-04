<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-catalog');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'max:180', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('vehicles')->ignore($this->route('vehicle'))],
            'segment' => ['nullable', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:10000'],
            'specifications' => ['nullable', 'json', 'max:10000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $decoded = json_decode($value, true);
                    if (! is_array($decoded) || ($decoded !== [] && array_is_list($decoded))) {
                        $fail('Thông số phải là JSON gồm tên thuộc tính và giá trị.');
                    }
                }], 'media_id' => ['nullable', 'exists:media,id'],
            'brochure_url' => ['nullable', 'url:http,https', 'max:255'], 'is_active' => ['required', 'boolean'],
            'variants' => ['nullable', 'array', 'max:30'], 'variants.*' => ['array:name,price'],
            'variants.*.name' => ['required', 'string', 'max:100', 'distinct'],
            'variants.*.price' => ['nullable', 'integer', 'min:0', 'max:999999999999999'],
            'colors' => ['nullable', 'array', 'max:30'], 'colors.*' => ['array:name,hex,secondary_hex,media_id'],
            'colors.*.name' => ['required', 'string', 'max:100', 'distinct'],
            'colors.*.hex' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
            'colors.*.secondary_hex' => ['nullable', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
            'colors.*.media_id' => ['nullable', 'exists:media,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['variants', 'colors'] as $key) {
            if (is_array($this->input($key))) {
                $this->merge([$key => array_values(array_filter($this->input($key),
                    fn (mixed $row): bool => ! is_array($row) || ! empty($row['name'])))]);
            }
        }
    }
}
