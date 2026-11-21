@extends('layouts.app', ['area' => ($admin ?? false) ? 'admin' : 'my'])
@section('title', $title)
@section('content')
<div @class(['stack', 'booking-details' => !($admin ?? false)])>
    <header class="booking-details-heading">
        <div>
            <h1>{{ $title }}</h1>
            <p><span>{{ $booking->code }}</span> <strong @class(['booking-status', 'booking-status--confirmed' => $booking->status === 'confirmed'])>{{ ucfirst($booking->status) }}</strong></p>
        </div>
    @unless($admin ?? false)
        <nav class="booking-page-navigation" aria-label="Page history">
            <button class="button secondary" type="button" aria-label="Previous page" title="Previous page" onclick="history.back()">←</button>
            <button class="button secondary" type="button" aria-label="Next page" title="Next page" onclick="history.forward()">→</button>
        </nav>
    @endunless
    </header>
    @if($paid && $booking->status !== 'confirmed' && $booking->status !== 'completed')
        <p class="notice" role="status">Payment received. Contact support to review this booking before paying again.</p>
    @endif
    @if($hold)
        <p class="notice">Seats held until {{ now()->setTimestamp($hold['expires'])->format('H:i T') }}.</p>
    @elseif($booking->status === 'pending' && !$paid && !($admin ?? false))
        <p class="notice">Your seat hold has expired. Search again to choose available seats.</p>
    @endif
    <div class="booking-details-grid">
    <section class="panel stack">
        <h2>Itinerary</h2>
        @foreach($booking->bookingFlights as $leg)
            <div class="booking-itinerary-leg">
                <h3>{{ ucfirst($leg->direction) }} · {{ $leg->flight->flight_number }}</h3>
                <p>{{ $leg->flight->originAirport->code }} → {{ $leg->flight->destinationAirport->code }} · {{ $leg->flight->departure_at->format('d M Y, H:i') }} · {{ ucfirst($leg->flightClass->class) }}</p>
                @if(isset($hold['seats'][$leg->direction]))
                    <p>Held seats: {{ collect($hold['seats'][$leg->direction])->map(fn ($id) => $heldSeats->get($id))->implode(', ') }}</p>
                @endif
                @foreach($leg->tickets as $ticket)
                    <div class="booking-ticket-row">
                        <p><strong>{{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}</strong> <span>· Seat {{ $ticket->seat?->seat_number }} · Ticket {{ $ticket->ticket_number }}</span></p>
                        @if(!($admin ?? false) && $ticket->status === 'issued' && in_array($booking->status, ['confirmed', 'completed'], true))
                            <a class="button secondary" href="{{ route('my.ticket', $ticket->ticket_number) }}">View ticket<span class="sr-only"> for {{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}</span></a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endforeach
    </section>
    <section class="panel booking-travelers">
        <h2>Travelers</h2>
        <ul>@foreach($booking->passengers as $passenger)<li>{{ $passenger->first_name }} {{ $passenger->last_name }}</li>@endforeach</ul>
        <p class="booking-total">Total <strong>JOD {{ $booking->total }}</strong></p>
    </section>
    </div>
    @unless($admin ?? false)
        <div class="actions">
            <a href="{{ route('my.bookings') }}">My bookings</a>
            @if($booking->status === 'pending' && !$paid)
                @if($hold)
                    <a class="button" href="{{ route('book.payment', ['booking_id' => $booking->id]) }}">Continue to payment</a>
                @else
                    <a class="button" href="{{ route('flights.search') }}">Search again</a>
                @endif
                <form method="POST" action="{{ route('my.booking.cancel', $booking->code) }}">
                    @csrf
                    <button class="button secondary" type="submit">Cancel unpaid booking</button>
                </form>
            @elseif($booking->status === 'confirmed')
                <a href="{{ route('contact') }}">Contact support about cancellation</a>
            @endif
        </div>
    @endunless
</div>
@endsection
