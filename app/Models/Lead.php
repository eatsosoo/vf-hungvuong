<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = ['type',
        'name',
        'phone',
        'email',
        'message',
        'source',
        'vehicle_id',
        'vehicle_variant_id',
        'assigned_to',
        'status',
        'preferred_at',
        'appointment_at',
        'location',
        'quote_amount',
        'quote_details',
        'consented_at'];

    protected function casts(): array
    {
        return ['status' => LeadStatus::class, 'phone' => 'encrypted', 'email' => 'encrypted',
            'message' => 'encrypted', 'quote_details' => 'encrypted', 'preferred_at' => 'datetime',
            'appointment_at' => 'datetime', 'consented_at' => 'datetime', 'quote_amount' => 'decimal:0'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VehicleVariant::class, 'vehicle_variant_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function scopeAccessibleTo(Builder $query, User $user): void
    {
        if ($user->role === UserRole::Sales) {
            $query->where('assigned_to', $user->id);
        }
    }
}
