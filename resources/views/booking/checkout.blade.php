@extends('layouts.app')

@php
    $demoFlights = require resource_path('views/shared/demo-flights.php');
    $selection = array_filter(request()->only(['flight', 'class']), 'is_string');
    $flight = $demoFlights->firstWhere('id', (int) ($selection['flight'] ?? 0));
    $class = $selection['class'] ?? '';
    $fare = $flight['fares'][$class] ?? null;
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
@if($flight)
    <p>{{ $flight['number'] }} · {{ $flight['from'] }} → {{ $flight['to'] }} @if($fare) · {{ ucfirst($class) }} · JOD {{ $fare[0] }} @endif</p>
@else
    <p>Choose a flight and fare to see sample trip details. <a href="{{ route('flights.search') }}">Find flights</a></p>
@endif
@switch($step)
    @case('passengers')
        <section class="panel">
            <h2>Traveler and contact details</h2>
            <p>Passenger submission and passport scanning are currently unavailable.</p>
            <fieldset class="form-grid" disabled>
                <legend>Passenger 1</legend>
                <x-travel-field name="first_name" label="First name" value="Sample" />
                <x-travel-field name="last_name" label="Last name" value="Traveler" />
                <x-travel-field name="date_of_birth" label="Date of birth" type="date" />
                <x-travel-field name="nationality" label="Nationality" />
                <x-travel-field name="passport_number" label="Passport number" />
                <x-travel-field name="passport_country" label="Passport country" />
                <x-travel-field name="passport_expiry" label="Passport expiry" type="date" />
                <x-travel-field name="contact_email" label="Contact email" type="email" />
                <x-travel-field name="contact_phone" label="Contact phone" type="tel" />
            </fieldset>
            <a class="button section" href="{{ route('book.seats', $selection) }}">Preview seat selection</a>
        </section>
        @break
    @case('seats')
        <section class="panel">
            <h2>Choose a sample seat</h2>
            <p>Seat selection is local to this preview. Live holds are unavailable.</p>
            <x-seat-map interactive />
            <a class="button section" href="{{ route('book.review', $selection) }}">Review sample trip</a>
        </section>
        @break
    @case('review')
        <section class="panel">
            <h2>Trip summary</h2>
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
