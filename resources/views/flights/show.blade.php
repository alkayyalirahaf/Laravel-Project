@extends('layouts.app')

@php
    $demoFlights = require resource_path('views/shared/demo-flights.php');
@endphp
@php
    $flight = $demoFlights->firstWhere('id', (int) request()->route('flight'));
@endphp

@section('title'){{ $title }}@endsection

@section('content')
<h1>{{ $title }}</h1>
@if($flight)
    <p class="notice">Sample flight and fares</p>
    <div class="panel">
        <span class="badge">{{ $flight['number'] }}</span>
        <h2 class="section">{{ $flight['origin'] }} → {{ $flight['destination'] }}</h2>
        <p>{{ $flight['departure'] }} → {{ $flight['arrival'] }} · {{ $flight['duration'] }}</p>
    </div>
    <section class="section">
        <h2>Choose a cabin</h2>
        <div class="cards">
            @foreach($flight['fares'] as $class => [$price, $cabin, $checked])
                <article class="panel">
                    <h3>{{ ucfirst($class) }}</h3><p>JOD {{ $price }}</p><p>{{ $cabin }} kg cabin · {{ $checked }} kg checked</p>
                    <a class="button" href="{{ route('book.passengers', ['flight' => $flight['id'], 'class' => $class]) }}">Continue with {{ $class }}</a>
                </article>
            @endforeach
        </div>
    </section>
    <section class="section"><h2>Traveler reviews</h2><p class="panel">No published reviews for this sample flight.</p></section>
@else
    <p class="panel">This flight is not available in the preview. <a href="{{ route('flights.search') }}">Browse flights</a></p>
@endif
@endsection
