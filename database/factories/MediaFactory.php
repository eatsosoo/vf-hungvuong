<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    public function definition(): array
    {
        return ['path' => 'images/'.fake()->uuid().'.webp',
            'original_name' => 'image.webp',
            'width' => 100,
            'height' => 100,
            'size' => 1000];
    }
}
