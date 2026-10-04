<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['tin-vinfast' => 'Tin VinFast', 'danh-gia-xe' => 'Đánh giá xe', 'so-sanh-xe' => 'So sánh xe',
            'kinh-nghiem-su-dung' => 'Kinh nghiệm sử dụng', 'uu-dai' => 'Ưu đãi'] as $slug => $name) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name' => $name]);
        }
    }
}
