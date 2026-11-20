@extends('layouts.app')
@section('title', 'Booking lookup')
@section('content')
<h1>Booking lookup</h1>
@if(auth()->user()?->customer)
    <form class="panel narrow stack" method="POST" action="{{ route('booking.lookup.search') }}">
        @csrf
        <x-travel-field name="code" label="Booking reference" required maxlength="12" />
        <div><button class="button">Find booking</button></div>
    </form>
@else
    <p>Sign in to find a booking in your account.</p>
    <a class="button" href="{{ route('login') }}">Sign in</a>
@endif
@endsection
