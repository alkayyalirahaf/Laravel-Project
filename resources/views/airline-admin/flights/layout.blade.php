@extends('layouts.app')

@php
    $area = 'airline';
    $navigation = [
        'dashboard' => ['', 'Dashboard'],
        'flights' => ['/flights', 'Flights'],
        'create' => ['/flights/create', 'Create Flight'],
        'edit' => ['/flights/{flight}/edit', 'Edit Flight'],
        'flight' => ['/flights/{flight}', 'Flight Details'],
        'bookings' => ['/bookings', 'Bookings'],
        'booking' => ['/bookings/{id}', 'Booking Details'],
        'reviews' => ['/reviews', 'Reviews'],
    ];
@endphp

@section('navigation')
    <a href="{{ route('airline.dashboard') }}">Dashboard</a>
    <a href="{{ route('airline.flights') }}">Flights</a>
    <a href="{{ route('airline.bookings') }}">Bookings</a>
    <a href="{{ route('airline.reviews') }}">Reviews</a>
    @if(\Illuminate\Support\Facades\Route::has('logout'))
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="button secondary" type="submit">{{ __('Sign out') }}</button>
        </form>
    @endif
@endsection

@section('content')
<p class="eyebrow">Airline desk</p>
@yield('flight-content')
@endsection
