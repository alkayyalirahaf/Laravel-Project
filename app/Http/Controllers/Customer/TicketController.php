<?php

namespace App\Http\Controllers\Customer;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController
{
    public function show(Request $request, string $number)
    {
        $ticket = Ticket::query()
            ->where('ticket_number', $number)
            ->where('status', 'issued')
            ->whereHas('bookingFlight.booking', fn ($query) => $query
                ->where('customer_id', $request->user()->customer->id)
                ->whereIn('status', ['confirmed', 'completed']))
            ->with([
                'passenger', 'seat', 'bookingFlight.booking', 'bookingFlight.flightClass',
                'bookingFlight.flight.originAirport', 'bookingFlight.flight.destinationAirport',
                'bookingFlight.flight.airline',
            ])
            ->firstOrFail();

        return response()->view('booking.ticket', ['ticket' => $ticket])
            ->header('Cache-Control', 'private, no-store');
    }
}
