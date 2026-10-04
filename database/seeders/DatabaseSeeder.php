<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CategorySeeder::class,
            PageSeeder::class,
            VehicleSeeder::class,
            VehicleColorSeeder::class,
            SamplePromotionSeeder::class,
            SamplePostSeeder::class,
        ]);
    }
}
