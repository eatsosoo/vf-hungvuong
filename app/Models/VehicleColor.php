<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleColor extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_id', 'name', 'hex', 'secondary_hex', 'media_id'];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
