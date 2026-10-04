<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleVariant extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'name', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:0'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
