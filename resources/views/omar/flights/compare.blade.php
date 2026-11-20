@extends('omar.layout', ['title' => 'Compare flights'])

@section('omar-content')
    <h1>Compare flights</h1>
    @if($flights->isEmpty())
        <p>Select 2 or 3 flights from <a href="{{ route('flights.search') }}">search results</a>.</p>
    @else
        <p>Times use {{ config('app.timezone') }}. Prices are per-seat base fares.</p>
        <div class="table-responsive"><table class="table table-bordered">
            <thead><tr><th>Feature</th>@foreach($flights as $flight)<th><a href="{{ route('flights.show', $flight) }}">{{ $flight->flight_number }}</a></th>@endforeach</tr></thead>
            <tbody>
                <tr><th>Airline</th>@foreach($flights as $flight)<td>{{ $flight->airline->name }}</td>@endforeach</tr>
                <tr><th>Route</th>@foreach($flights as $flight)<td>{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}</td>@endforeach</tr>
                <tr><th>Departure</th>@foreach($flights as $flight)<td>{{ $flight->departure_at->format('Y-m-d H:i') }}</td>@endforeach</tr>
                <tr><th>Arrival</th>@foreach($flights as $flight)<td>{{ $flight->arrival_at->format('Y-m-d H:i') }}</td>@endforeach</tr>
                <tr><th>Cabins, fares, baggage and seats</th>@foreach($flights as $flight)<td>
                    @forelse($flight->flightClasses as $class)
                        <p>{{ ucfirst($class->class) }}: {{ $class->price }}<br>Cabin {{ $class->cabin_baggage_kg }} kg; checked {{ $class->checked_baggage_kg }} kg<br>{{ $class->available_seats_count }} available seats</p>
                    @empty No cabins published. @endforelse
                </td>@endforeach</tr>
            </tbody>
        </table></div>
    @endif
@endsection
