@extends('layouts.app', ['area' => ($admin ?? false) ? 'admin' : 'my'])
@section('title', ($admin ?? false) ? 'Bookings' : 'My bookings')
@section('content')
<h1>{{ ($admin ?? false) ? 'Bookings' : 'My bookings' }}</h1>
<div class="panel table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Status</th><th>Total</th><th>Date booked</th></tr></thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <th scope="row"><a href="{{ ($admin ?? false) ? route('admin.booking', $booking->id) : route('my.booking', $booking->code) }}">{{ $booking->code }}</a></th>
                    <td>{{ ucfirst($booking->status) }}</td><td>JOD {{ $booking->total }}</td><td>{{ $booking->created_at->format('d M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No bookings yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $bookings->links() }}
@if(!($admin ?? false))
    <div class="actions section"><a href="{{ route('booking.lookup') }}">Look up a booking</a><a href="{{ route('my.profile') }}">Traveler profile</a></div>
@endif
@endsection
