<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    public function definition(): array
    {
        return ['type' => 'consultation',
            'name' => fake()->name(),
            'phone' => '0901234567',
            'status' => 'new',
            'source' => 'website',
            'consented_at' => now()];
    }
}
