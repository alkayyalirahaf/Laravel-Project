@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="payment-page stack">
<header>
    @if($booking)
        <a class="selection-back" href="{{ route('book.review', ['booking_id' => $booking->getKey()]) }}">← Back to booking review</a>
    @endif
    <h1 class="flight-page-title">{{ $title }}</h1>
</header>

@if($booking === null)
    <section class="panel">
        <p class="notice" role="alert">We couldn’t find that booking. Check the booking link or return to <a class="button secondary" href="{{ route('my.bookings') }}">My bookings</a>.</p>
    </section>
@else
<section class="panel stack payment-summary">
    <h2>Booking {{ $booking->code }}</h2>
    <dl class="payment-breakdown">
        <div><dt>Booking total</dt><dd>JOD {{ number_format((float) $booking->total, 2) }}</dd></div>
        <div><dt>Credits applied</dt><dd>JOD {{ number_format((float) $booking->credits_used, 2) }}</dd></div>
        <div class="payment-due"><dt>Payable now</dt><dd>JOD {{ $amountDueJod }}</dd></div>
    </dl>
    <p class="payment-conversion">You will be charged <strong>{{ $amountDueUsd }} {{ strtoupper($stripeCurrency) }}</strong> at 1 JOD = {{ $jodToUsdRate }} USD.</p>

    @if($cancelled)
        <x-toast>Checkout was cancelled. No payment was taken.</x-toast>
    @endif

    <form action="{{ route('book.payment.intent') }}" method="POST" class="stack">
        @csrf
        <input type="hidden" name="booking_id" value="{{ $booking->getKey() }}">
        <button class="button payment-submit" type="submit">Pay with Stripe <span aria-hidden="true">→</span></button>
    </form>
</section>
@endif
</div>
@endsection
