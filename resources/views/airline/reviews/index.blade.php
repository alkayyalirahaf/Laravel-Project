@extends('airline-admin.flights.layout')

@section('title', 'Airline Reviews')

@section('flight-content')
<div class="flight-card">
    <div><h1>Reviews</h1><p>Customer feedback for your airline's flights.</p></div>
</div>

<section class="panel table-wrap section">
    <table>
        <caption>Flight reviews</caption>
        <thead>
            <tr>
                <th scope="col">Flight</th>
                <th scope="col">Customer</th>
                <th scope="col">Rating</th>
                <th scope="col">Comment</th>
                <th scope="col">Status</th>
                <th scope="col">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reviews as $review)
                <tr>
                    <th scope="row">
                        @if($review->bookingFlight?->flight)
                            <a href="{{ route('airline.flight', $review->bookingFlight->flight) }}">{{ $review->bookingFlight->flight->flight_number }}</a>
                        @else
                            —
                        @endif
                    </th>
                    <td>{{ $review->customer?->user?->name ?? '—' }}</td>
                    <td>{{ $review->rating }} / 5</td>
                    <td>{{ $review->comment ?: '—' }}</td>
                    <td><span class="badge">{{ ucfirst($review->status) }}</span></td>
                    <td>{{ $review->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No reviews found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

{{ $reviews->links() }}
@endsection
