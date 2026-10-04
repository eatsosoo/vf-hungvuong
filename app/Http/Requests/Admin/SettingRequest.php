<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-settings');
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:100'], 'seo_description' => ['nullable', 'string', 'max:500'],
            'hotline' => ['nullable', 'regex:/\A\+?[0-9 ()-]{9,20}\z/'],
            'zalo_url' => ['nullable', 'url:https', 'max:255'], 'map_url' => ['nullable', 'url:https', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'], 'opening_hours' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'], 'hero_description' => ['nullable', 'string', 'max:1000'],
            'banner_media_id' => ['nullable', 'exists:media,id'], 'notify_leads' => ['required', 'boolean'],
        ];
    }
}
