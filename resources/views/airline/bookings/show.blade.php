@extends('airline-admin.flights.layout')

@section('title', 'Booking '.$booking->code)

@section('flight-content')
<div class="flight-card">
    <div>
        <p class="eyebrow">Booking details</p>
        <h1>{{ $booking->code }}</h1>
    </div>
    <span class="badge">{{ ucfirst($booking->status) }}</span>
</div>

@php
    $successfulPayment = $booking->payments->firstWhere('status', 'succeeded');
    $latestPayment = $booking->payments->sortByDesc('id')->first();
@endphp
<section class="cards section">
    <article class="panel">
        <p>Customer</p>
        <h2>{{ $booking->customer?->user?->name ?? '—' }}</h2>
        <p>{{ $booking->contact_email }}</p>
    </article>
    <article class="panel">
        <p>Trip type</p>
        <h2>{{ ucfirst(str_replace('_', ' ', $booking->trip_type)) }}</h2>
        <p>Created {{ $booking->created_at->format('Y-m-d H:i') }}</p>
    </article>
    <article class="panel">
        <p>Payment status</p>
        <h2>{{ $successfulPayment ? 'Paid' : ($latestPayment ? ucfirst($latestPayment->status) : 'Not paid') }}</h2>
        <p>JOD {{ number_format((float) $booking->amount_paid, 2) }} paid of JOD {{ number_format((float) $booking->total, 2) }}</p>
    </article>
</section>

<section class="panel table-wrap section">
    <h2>Passengers</h2>
    <table>
        <caption>Passengers on this booking</caption>
        <thead><tr><th scope="col">Name</th><th scope="col">Type</th><th scope="col">Nationality</th><th scope="col">Email</th></tr></thead>
        <tbody>
            @forelse($booking->passengers as $passenger)
                <tr>
                    <th scope="row">{{ trim($passenger->first_name.' '.$passenger->last_name) }}</th>
                    <td>{{ ucfirst($passenger->type) }}</td>
                    <td>{{ $passenger->nationality ?: '—' }}</td>
                    <td>{{ $passenger->email ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No passenger details are available.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="section">
    <h2>Flights and tickets</h2>
    <div class="stack">
        @forelse($booking->bookingFlights as $bookingFlight)
            <article class="panel stack">
                <div class="flight-card">
                    <div>
                        <h3>{{ $bookingFlight->flight->flight_number }}</h3>
                        <p>{{ $bookingFlight->flight->originAirport->code }} → {{ $bookingFlight->flight->destinationAirport->code }}</p>
                    </div>
                    <div class="actions">
                        <span class="badge">{{ ucfirst($bookingFlight->direction) }}</span>
                        <span class="badge">{{ ucfirst($bookingFlight->flightClass->class) }}</span>
                        <a class="quiet" href="{{ route('airline.flight', $bookingFlight->flight) }}">View flight</a>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <caption>Issued tickets</caption>
                        <thead><tr><th scope="col">Passenger</th><th scope="col">Ticket number</th><th scope="col">Seat</th><th scope="col">Status</th></tr></thead>
                        <tbody>
                            @forelse($bookingFlight->tickets as $ticket)
                                <tr>
                                    <th scope="row">{{ trim($ticket->passenger->first_name.' '.$ticket->passenger->last_name) }}</th>
                                    <td>{{ $ticket->ticket_number }}</td>
                                    <td>{{ $ticket->seat?->seat_number ?? '—' }}</td>
                                    <td><span class="badge">{{ ucfirst($ticket->status) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">No tickets have been issued for this flight.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @empty
            <p class="panel">No flights belonging to this airline are attached to the booking.</p>
        @endforelse
    </div>
</section>

<section class="panel table-wrap section">
    <h2>Payments</h2>
    <table>
        <caption>Payment records</caption>
        <thead><tr><th scope="col">Status</th><th scope="col">Amount</th><th scope="col">Receipt</th><th scope="col">Paid at</th></tr></thead>
        <tbody>
            @forelse($booking->payments as $payment)
                <tr>
                    <th scope="row"><span class="badge">{{ ucfirst($payment->status) }}</span></th>
                    <td>JOD {{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->receipt_number ?? '—' }}</td>
                    <td>{{ $payment->paid_at?->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No payment record.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="actions section">
    @if($booking->status !== 'cancelled')
        <form method="POST" action="{{ route('airline.bookings.cancel', $booking) }}" onsubmit="return confirm('Cancel this booking and release its seats?')">
            @csrf
            <button type="submit" class="quiet">Cancel booking</button>
        </form>
    @endif
    <a class="button secondary" href="{{ route('airline.bookings') }}">Back to bookings</a>
</div>
@endsection
