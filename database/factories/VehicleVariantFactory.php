<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleVariantFactory extends Factory
{
    public function definition(): array
    {
        return ['vehicle_id' => Vehicle::factory(), 'name' => fake()->unique()->word(), 'price' => 500000000];
    }
}
