<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingFlight extends Model
{
    protected $fillable = [
        'booking_id',
        'flight_id',
        'flight_class_id',
        'direction',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class, 'flight_id');
    }

    public function flightClass(): BelongsTo
    {
        return $this->belongsTo(FlightClass::class, 'flight_class_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'booking_flight_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'booking_flight_id');
    }
}
