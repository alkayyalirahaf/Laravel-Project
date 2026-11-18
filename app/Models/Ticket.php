<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'booking_flight_id',
        'passenger_id',
        'seat_id',
        'ticket_number',
        'qr_code',
        'pdf_path',
        'status',
    ];

    public function bookingFlight(): BelongsTo
    {
        return $this->belongsTo(BookingFlight::class, 'booking_flight_id');
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class, 'passenger_id');
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class, 'seat_id');
    }
}
