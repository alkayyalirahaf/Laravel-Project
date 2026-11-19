@extends('layouts.app')

@section('title', $title)

@section('content')
<h1>{{ $title }}</h1>

@if($booking === null)
    <section class="panel narrow">
        <p class="notice" role="alert">We couldn’t find that booking. Check the booking link or return to <a href="{{ route('my.bookings') }}">My bookings</a>.</p>
    </section>
@else
<section class="panel narrow">
    <h2>Booking {{ $booking->code }}</h2>
    <p>Booking total: JOD {{ number_format((float) $booking->total, 2) }}</p>
    <p>Credits applied: JOD {{ number_format((float) $booking->credits_used, 2) }}</p>
    <p><strong>Payable now: JOD {{ $amountDueJod }}</strong></p>
    <p class="notice">You will be charged {{ $amountDueUsd }} {{ strtoupper($stripeCurrency) }} at 1 JOD = {{ number_format((float) $jodToUsdRate, 2) }} USD.</p>

    @if($cancelled)
        <p class="notice" role="status">Checkout was cancelled. No payment was taken.</p>
    @endif

    <form action="{{ route('book.payment.intent') }}" method="POST" class="stack">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->getKey() }}">
        <button class="button" type="submit">Pay with Stripe</button>
    </form>
</section>
@endif
@endsection
