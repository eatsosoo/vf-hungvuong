<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PromotionFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'body' => 'Thông tin ưu đãi',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true];
    }
}
