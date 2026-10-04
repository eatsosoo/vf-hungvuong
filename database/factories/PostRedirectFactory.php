<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostRedirectFactory extends Factory
{
    public function definition(): array
    {
        return ['post_id' => Post::factory(), 'slug' => fake()->unique()->slug()];
    }
}
