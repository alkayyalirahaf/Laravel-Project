<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightClass extends Model
{
    protected $fillable = [
        'flight_id',
        'class',
        'price',
        'seat_rows',
        'seats_per_row',
        'total_seats',
        'cabin_baggage_kg',
        'checked_baggage_kg',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'seat_rows' => 'integer',
            'seats_per_row' => 'integer',
            'total_seats' => 'integer',
            'cabin_baggage_kg' => 'integer',
            'checked_baggage_kg' => 'integer',
        ];
    }

    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class, 'flight_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class, 'flight_class_id');
    }

    public function bookingFlights(): HasMany
    {
        return $this->hasMany(BookingFlight::class, 'flight_class_id');
    }
}
