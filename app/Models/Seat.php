<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    protected $fillable = [
        'flight_class_id',
        'seat_number',
        'status',
        'held_by',
        'held_until',
    ];

    protected function casts(): array
    {
        return [
            'held_until' => 'datetime',
        ];
    }

    public function scopeAvailableForBooking(Builder $query): Builder
    {
        return $query->where(fn ($query) => $query->where('status', 'available')
            ->orWhere(fn ($query) => $query->where('status', 'held')->where('held_until', '<=', now())));
    }

    public function flightClass(): BelongsTo
    {
        return $this->belongsTo(FlightClass::class, 'flight_class_id');
    }

    public function heldBy(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'held_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'seat_id');
    }
}
