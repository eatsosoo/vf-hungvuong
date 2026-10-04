<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return ['action' => 'updated', 'subject_type' => Post::class, 'subject_id' => 1, 'changed_fields' => ['title']];
    }
}
