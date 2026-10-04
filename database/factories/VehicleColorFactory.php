<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleColorFactory extends Factory
{
    public function definition(): array
    {
        return ['vehicle_id' => Vehicle::factory(), 'name' => fake()->unique()->colorName(), 'hex' => '#ffffff'];
    }
}
