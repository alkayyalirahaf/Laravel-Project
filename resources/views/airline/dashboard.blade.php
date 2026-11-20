@extends('airline-admin.flights.layout')

@section('title', 'Airline Dashboard')

@section('flight-content')
<div class="flight-card">
    <div><h1>Dashboard</h1><p>Overview of your airline's flights, bookings, and customer feedback.</p></div>
    <div class="actions">
        <a class="button secondary" href="{{ route('airline.flights') }}">Flights</a>
        <a class="button secondary" href="{{ route('airline.bookings') }}">Bookings</a>
        <a class="button secondary" href="{{ route('airline.reviews') }}">Reviews</a>
    </div>
</div>

<section class="cards section" aria-label="Airline performance">
    <article class="panel"><p>Total flights</p><h2>{{ number_format($totalFlights) }}</h2></article>
    <article class="panel"><p>Upcoming scheduled flights</p><h2>{{ number_format($upcomingFlightsCount) }}</h2></article>
    <article class="panel"><p>Total bookings</p><h2>{{ number_format($totalBookings) }}</h2></article>
    <article class="panel"><p>Total revenue</p><h2>JOD {{ number_format((float) $totalRevenue, 2) }}</h2></article>
    <article class="panel"><p>Seat occupancy</p><h2>{{ number_format((float) $seatOccupancyRate, 1) }}%</h2></article>
    <article class="panel"><p>Average rating</p><h2>{{ $averageRating === null ? '—' : number_format((float) $averageRating, 1).' / 5' }}</h2></article>
</section>

<section class="panel table-wrap section">
    <h2>Upcoming flights</h2>
    <table>
        <caption>The next scheduled departures</caption>
        <thead>
            <tr>
                <th scope="col">Flight</th>
                <th scope="col">Route</th>
                <th scope="col">Departure</th>
                <th scope="col">Status</th>
                <th scope="col">Available seats</th>
                <th scope="col">Details</th>
            </tr>
        </thead>
        <tbody>
            @forelse($upcomingFlights as $flight)
                <tr>
                    <th scope="row">{{ $flight->flight_number }}</th>
                    <td>{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}</td>
                    <td>{{ $flight->departure_at->format('Y-m-d H:i') }}</td>
                    <td><span class="badge">{{ ucfirst($flight->status) }}</span></td>
                    <td>{{ $flight->flightClasses->sum(fn ($flightClass) => $flightClass->seats->count()) }}</td>
                    <td><a class="quiet" href="{{ route('airline.flight', $flight) }}">View flight</a></td>
                </tr>
            @empty
                <tr><td colspan="6">No upcoming flights.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="cards section">
    <article class="panel table-wrap">
        <div class="flight-card"><h2>Recent bookings</h2><a href="{{ route('airline.bookings') }}">All bookings</a></div>
        <table>
            <caption>Latest bookings on your flights</caption>
            <thead><tr><th scope="col">Booking</th><th scope="col">Customer</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @forelse($recentBookings as $booking)
                    <tr>
                        <th scope="row"><a href="{{ route('airline.booking', $booking) }}">{{ $booking->code }}</a></th>
                        <td>{{ $booking->customer?->user?->name ?? '—' }}</td>
                        <td><span class="badge">{{ ucfirst($booking->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3">No recent bookings.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>

    <article class="panel table-wrap">
        <div class="flight-card"><h2>Recent reviews</h2><a href="{{ route('airline.reviews') }}">All reviews</a></div>
        <table>
            <caption>Latest reviews for your flights</caption>
            <thead><tr><th scope="col">Flight</th><th scope="col">Rating</th><th scope="col">Review</th></tr></thead>
            <tbody>
                @forelse($recentReviews as $review)
                    <tr>
                        <th scope="row">{{ $review->bookingFlight?->flight?->flight_number ?? '—' }}</th>
                        <td>{{ $review->rating }} / 5</td>
                        <td>{{ \Illuminate\Support\Str::limit($review->comment ?? '', 90) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No recent reviews.</td></tr>
                @endforelse
            </tbody>
        </table>
    </article>
</section>
@endsection
