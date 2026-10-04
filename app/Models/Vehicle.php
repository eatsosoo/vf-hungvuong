<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = ['name',
        'slug',
        'segment',
        'description',
        'specifications',
        'media_id',
        'brochure_url',
        'is_active'];

    protected function casts(): array
    {
        return ['specifications' => 'array', 'is_active' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(VehicleVariant::class);
    }

    public function colors(): HasMany
    {
        return $this->hasMany(VehicleColor::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
