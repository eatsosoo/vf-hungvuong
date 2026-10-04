<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'VinFast '.fake()->unique()->bothify('VF ##'),
            'slug' => fake()->unique()->slug(),
            'is_active' => true,
            'segment' => 'SUV'];
    }
}
