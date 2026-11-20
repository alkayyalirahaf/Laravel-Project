@extends('layouts.app')

@section('title', $title ?? 'Airline Admin Login')

@section('content')
<div class="narrow">
    <p class="eyebrow">Airline staff</p>
    <h1>{{ $title ?? 'Airline Admin Login' }}</h1>
    <p>Sign in to manage your airline's flights and bookings.</p>

    @if(\Illuminate\Support\Facades\Route::has('airline.login.submit'))
    <form method="POST" action="{{ route('airline.login.submit') }}" class="panel stack">
        @csrf

        <x-travel-field name="email" label="Email address" type="email" autocomplete="username" required autofocus />
        <x-travel-field name="password" label="Password" type="password" autocomplete="current-password" required />

        <label class="actions">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Remember me</span>
        </label>

        <button type="submit" class="button">Sign in</button>
    </form>
    @else
        <p class="notice" role="status">Airline sign-in is temporarily unavailable. Please try again later.</p>
    @endif
</div>
@endsection
