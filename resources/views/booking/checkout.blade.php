@extends('layouts.app')

@php
    $demoFlights = require resource_path('views/shared/demo-flights.php');
    $selection = array_filter(request()->only(['flight', 'class']), 'is_string');
    $flight = $demoFlights->firstWhere('id', (int) ($selection['flight'] ?? 0));
    $class = $selection['class'] ?? '';
    $fare = $flight['fares'][$class] ?? null;
    $cabinRows = require resource_path('views/shared/demo-cabin.php');
    $selectedSeat = request()->query('seat');
    if (is_string($selectedSeat) && preg_match('/^(\d{2})([ACHK])$/D', $selectedSeat, $seatParts)
        && ($cabinRows[(int) $seatParts[1]][$seatParts[2]] ?? false)) {
        $selection['seat'] = $selectedSeat;
    }
@endphp

@section('title'){{ $title }}@endsection

@section('content')
<nav class="subnav" aria-label="Booking steps">
    @foreach($steps as $key => $label)
        <a href="{{ route("book.$key", $selection) }}" @if($key === $step) aria-current="step" @endif>{{ $loop->iteration }}. {{ $label }}</a>
    @endforeach
</nav>
<h1>{{ $title }}</h1>
<p class="notice">Booking preview. Do not enter real passport or payment details.</p>
@unless($step === 'passengers')
@if($flight)
    <p>{{ $flight['number'] }} · {{ $flight['from'] }} → {{ $flight['to'] }} @if($fare) · {{ ucfirst($class) }} · JOD {{ $fare[0] }} @endif</p>
@else
    <p>Choose a flight and fare to see sample trip details. <a href="{{ route('flights.search') }}">Find flights</a></p>
@endif
@endunless
@switch($step)
    @case('passengers')
        @include('booking.passengers')
        @break
    @case('seats')
        <section class="panel">
            <h2>Choose a sample seat</h2>
            <p>Seat selection is local to this preview. Live holds are unavailable.</p>
            <form method="GET" action="{{ route('book.review') }}">
                @foreach(\Illuminate\Support\Arr::except($selection, 'seat') as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <x-booking-seat-map :rows="$cabinRows" :selected="$selection['seat'] ?? null" />
                <button class="button section">Review sample trip</button>
            </form>
        </section>
        @break
    @case('review')
        <section class="panel">
            <h2>Trip summary</h2>
            @isset($selection['seat'])
                <p>Selected seat <strong>{{ $selection['seat'] }}</strong></p>
            @endisset
            <p>{{ $flight ? 'Departs at '.$flight['departure'] : 'Choose a flight to see trip details.' }}</p>
            <p>{{ $fare ? 'Base fare per traveler: JOD '.$fare[0] : 'Choose a fare to see the base price.' }}</p>
            <p>Final taxes, credits, passenger details, and cancellation terms appear when checkout is available.</p>
            <a class="button" href="{{ route('book.payment', $selection) }}">Preview payment</a>
        </section>
        @break
    @case('payment')
        <section class="panel"><h2>Order payment</h2><p>Stripe checkout and credit redemption are currently unavailable.</p><button class="button" disabled>Pay securely</button> <a class="button secondary" href="{{ route('book.confirmation', $selection) }}">Preview confirmation</a></section>
        @break
    @case('confirmation')
        <section class="panel"><h2>No confirmed booking</h2><p>Confirmed reservations will appear in your bookings account once checkout is connected.</p><a class="button" href="{{ route('my.bookings') }}">Preview my bookings</a></section>
        @break
@endswitch
@endsection
