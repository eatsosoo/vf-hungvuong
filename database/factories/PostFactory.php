<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(),
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'body' => '## Nội dung\nBài viết thử nghiệm.',
            'status' => 'draft'];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published', 'published_at' => now()->subMinute()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => 'scheduled', 'published_at' => now()->addHour()]);
    }
}
