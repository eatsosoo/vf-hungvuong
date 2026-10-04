<?php

namespace App\Actions\Vehicles;

use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class SaveVehicle
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, Vehicle $vehicle): Vehicle
    {
        return DB::transaction(function () use ($data, $vehicle): Vehicle {
            $variants = $data['variants'] ?? [];
            $colors = $data['colors'] ?? [];
            unset($data['variants'], $data['colors']);
            $data['specifications'] = ! empty($data['specifications']) ? json_decode($data['specifications'],
                true) : null;
            $vehicle->fill($data)->save();
            foreach (['variants' => $variants, 'colors' => $colors] as $relation => $rows) {
                $names = array_column($rows, 'name');
                $vehicle->{$relation}()->whereNotIn('name', $names)->get()->each(fn ($row) => $row->delete());
                foreach ($rows as $row) {
                    $vehicle->{$relation}()->updateOrCreate(['name' => $row['name']], $row);
                }
            }

            return $vehicle;
        });
    }
}
