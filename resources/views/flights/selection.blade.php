@extends('layouts.app')

@section('title', 'Selected flights')

@section('content')
<h1>Selected flights</h1>
    <p class="notice">Your selection is saved for this session. Seats are not reserved and prices can change before booking.</p>
    <p>{{ $selection['passengers'] }} seats requested — {{ $selection['trip'] === 'round_trip' ? 'Round trip' : 'One way' }}</p>
    <p>Adults: {{ $selection['passenger_counts']['adults'] }} · Youth: {{ $selection['passenger_counts']['youth'] }} · Children: {{ $selection['passenger_counts']['children'] }} · Infants: {{ $selection['passenger_counts']['infants'] }}</p>
    <table class="table"><thead><tr><th>Leg</th><th>Flight</th><th>Base fare per seat</th></tr></thead><tbody>
        @foreach(['outbound' => 'Outbound', 'return' => 'Return'] as $key => $label)
            @if($selection[$key])<tr><td>{{ $label }}</td><td><a href="{{ route('flights.show', $selection[$key]['flight_id']) }}">Flight #{{ $selection[$key]['flight_id'] }}</a></td><td>{{ $selection[$key]['price'] }}</td></tr>@endif
        @endforeach
    </tbody></table>
    <a class="button" href="{{ route('book.passengers') }}">Continue to passengers</a>
    <a class="button secondary" href="{{ route('flights.search') }}">Search again</a>
@endsection
