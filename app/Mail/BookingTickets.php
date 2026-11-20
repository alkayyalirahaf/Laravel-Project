<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingTickets extends Mailable
{
    public function __construct(private readonly Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Yalla Teer tickets '.$this->booking->code);
    }

    public function content(): Content
    {
        $booking = $this->booking->load([
            'passengers.tickets' => fn ($query) => $query->where('status', 'issued'),
            'passengers.tickets.seat',
            'passengers.tickets.bookingFlight.flight.originAirport',
            'passengers.tickets.bookingFlight.flight.destinationAirport',
            'passengers.tickets.bookingFlight.flightClass',
        ]);

        return new Content(
            view: 'emails.booking-tickets',
            text: 'emails.booking-tickets-text',
            with: ['booking' => $booking],
        );
    }
}
