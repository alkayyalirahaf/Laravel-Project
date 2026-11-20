@extends('layouts.app')

@section('title', 'Flight details')

@section('content')
<h1>{{ $flight->flight_number }}</h1>
<div class="panel">
    <span class="badge">{{ $flight->airline->name }} · {{ ucfirst($flight->status) }}</span>
    <h2 class="section">{{ $flight->originAirport->name }} ({{ $flight->originAirport->code }}) → {{ $flight->destinationAirport->name }} ({{ $flight->destinationAirport->code }})</h2>
    <p>{{ $flight->departure_at->format('Y-m-d H:i') }} → {{ $flight->arrival_at->format('Y-m-d H:i') }} · {{ (int) $flight->departure_at->diffInMinutes($flight->arrival_at) }} minutes</p>
    <p>Times use {{ config('app.timezone') }}. Prices are per-seat base fares.</p>
</div>
<section class="section">
    <h2>Available cabins</h2>
    <div class="cards">
        @forelse($flight->flightClasses as $class)
            <article class="panel stack">
                <h3>{{ ucfirst($class->class) }}</h3><p>JOD {{ $class->price }}</p>
                <p>{{ $class->cabin_baggage_kg }} kg cabin · {{ $class->checked_baggage_kg }} kg checked</p>
                <p>{{ $class->available_seats_count }} seats available</p>
                <a class="button secondary" href="{{ route('flights.search', array_merge(session('dev3.search_filters', []), ['cabin' => $class->class])) }}">Search and select this cabin</a>
            </article>
        @empty
            <p class="panel">No cabins have been published.</p>
        @endforelse
    </div>
</section>
<a class="button secondary section" href="{{ route('flights.search', session('dev3.search_filters', [])) }}">Back to results and select a cabin</a>
@endsection
