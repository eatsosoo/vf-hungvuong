<?php

namespace App\Actions\Promotions;

use App\Models\Promotion;
use Illuminate\Support\Facades\DB;

class SavePromotion
{
    /** @param array<string, mixed> $data */
    public function handle(array $data, Promotion $record): Promotion
    {
        return DB::transaction(function () use ($data, $record): Promotion {
            $vehicles = $data['vehicles'] ?? [];
            unset($data['vehicles']);
            $record->fill($data)->save();
            $record->vehicles()->sync($vehicles);

            return $record;
        });
    }
}
