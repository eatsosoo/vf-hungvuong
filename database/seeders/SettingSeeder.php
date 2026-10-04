<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['site_name' => 'VinFast Hùng Vương',
            'seo_description' => 'Tìm hiểu xe điện VinFast, nhận tư vấn và đăng ký lái thử tại Hùng Vương.',
            'hero_title' => 'Xe điện tiên phong. Cho mọi hành trình.',
            'hero_description' => 'VinFast Hùng Vương đồng hành cùng bạn.',
            'notify_leads' => '1'] as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
