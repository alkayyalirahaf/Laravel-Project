@extends('omar.layout', ['title' => 'Flight details'])

@section('omar-content')
    <h1>{{ $flight->flight_number }}</h1>
    <dl class="row">
        <dt class="col-sm-3">Airline</dt><dd class="col-sm-9">{{ $flight->airline->name }}</dd>
        <dt class="col-sm-3">Departure</dt><dd class="col-sm-9">{{ $flight->originAirport->name }} ({{ $flight->originAirport->code }}) — {{ $flight->departure_at->format('Y-m-d H:i') }}</dd>
        <dt class="col-sm-3">Arrival</dt><dd class="col-sm-9">{{ $flight->destinationAirport->name }} ({{ $flight->destinationAirport->code }}) — {{ $flight->arrival_at->format('Y-m-d H:i') }}</dd>
        <dt class="col-sm-3">Duration</dt><dd class="col-sm-9">{{ $flight->arrival_at->greaterThan($flight->departure_at) ? (int) $flight->departure_at->diffInMinutes($flight->arrival_at) . ' minutes' : 'Unavailable' }}</dd>
        <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ ucfirst($flight->status) }}</dd>
    </dl>
    <p>Times use {{ config('app.timezone') }}. Prices are per-seat base fares.</p>
    <div class="table-responsive"><table class="table table-striped">
        <thead><tr><th>Cabin</th><th>Base fare</th><th>Cabin baggage</th><th>Checked baggage</th><th>Available seats</th></tr></thead>
        <tbody>@forelse($flight->flightClasses as $class)
            <tr><td>{{ ucfirst($class->class) }}</td><td>{{ $class->price }}</td><td>{{ $class->cabin_baggage_kg }} kg</td><td>{{ $class->checked_baggage_kg }} kg</td><td>{{ $class->available_seats_count }}</td></tr>
        @empty<tr><td colspan="5">No cabins have been published.</td></tr>@endforelse</tbody>
    </table></div>
    <a class="btn btn-primary" href="{{ route('flights.search') }}">Search and select a cabin</a>
@endsection
