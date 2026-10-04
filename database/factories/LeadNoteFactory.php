<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadNoteFactory extends Factory
{
    public function definition(): array
    {
        return ['lead_id' => Lead::factory(), 'user_id' => User::factory(), 'body' => 'Đã liên hệ khách hàng'];
    }
}
