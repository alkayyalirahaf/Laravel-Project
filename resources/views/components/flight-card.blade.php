@props(['flight', 'preferredCabin' => null])
@php
    if ($flight instanceof \App\Models\Flight) {
        $flight = [
            'id' => $flight->id, 'airline' => $flight->airline->name, 'number' => $flight->flight_number,
            'from' => $flight->originAirport->code, 'to' => $flight->destinationAirport->code,
            'departure' => $flight->departure_at->format('Y-m-d H:i'),
            'arrival' => $flight->arrival_at->format('Y-m-d H:i'),
            'duration' => (int) $flight->departure_at->diffInMinutes($flight->arrival_at).' minutes',
            'stops' => $flight->stops,
            'fares' => $flight->flightClasses->mapWithKeys(fn ($class) => [$class->class => [$class->price, $class->cabin_baggage_kg, $class->checked_baggage_kg]])->all(),
        ];
    }
@endphp
<article class="panel flight-card">
    <div>
        <span class="badge">{{ $flight['airline'] }} · {{ $flight['number'] }}</span>
        <h2>{{ $flight['from'] }} → {{ $flight['to'] }}</h2>
        <p>{{ $flight['departure'] }} → {{ $flight['arrival'] }} · {{ $flight['duration'] }} · {{ $flight['stops'] ? $flight['stops'].' stops' : 'Nonstop' }}</p>
        <p>{{ $flight['fares'] ? 'From JOD '.min(array_column($flight['fares'], 0)) : 'No available cabins' }}</p>
    </div>
    <div class="actions">
        {{ $slot }}
        <a class="button secondary" href="{{ route('flights.show', array_filter(['flight' => $flight['id'], 'cabin' => $preferredCabin])) }}">Flight details</a>
    </div>
</article>
