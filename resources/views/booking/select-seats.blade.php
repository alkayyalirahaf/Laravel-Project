@extends('layouts.app')
@section('title', 'Select seats')
@section('content')
<div class="stack">
    <h1>Select seats</h1>
    <p>Choose one seat for each traveler. Your seats are held for 15 minutes after you continue.</p>
    @if($errors->any())<p class="notice error" role="alert">{{ $errors->first() }}</p>@endif
    <form class="stack" method="POST" action="{{ route('book.seats.store') }}">
        @csrf
        <input type="hidden" name="booking_code" value="{{ $code }}">
        @foreach($cabins as $direction => $cabin)
            <section class="panel stack" data-seat-picker>
                <h2>{{ ucfirst($direction) }} · {{ $cabin->flight->flight_number }} · {{ ucfirst($cabin->class) }}</h2>
                <div class="actions seat-traveler-switch" data-seat-traveler-switch hidden>
                    @foreach($passengers as $index => $passenger)
                        <button type="button" class="button secondary" data-seat-traveler="{{ $index }}" aria-pressed="false">
                            {{ $passenger['first_name'] }} {{ $passenger['last_name'] }}
                            <span data-seat-summary></span>
                        </button>
                    @endforeach
                </div>
                <div class="seat-map-travelers">
                    @foreach($passengers as $index => $passenger)
                        <fieldset class="seat-map-traveler" data-seat-panel>
                            <legend>{{ $passenger['first_name'] }} {{ $passenger['last_name'] }}</legend>
                            <x-booking-seat-map :rows="$maps[$direction]['rows']"
                                :seat-ids="$maps[$direction]['ids']"
                                :name="'seats['.$direction.']['.$index.']'"
                                :selected="old('seats.'.$direction.'.'.$index)" />
                        </fieldset>
                    @endforeach
                </div>
            </section>
        @endforeach
        <div class="actions"><a href="{{ route('book.passengers') }}">Back to travelers</a><button class="button">Review booking</button></div>
    </form>
</div>
@endsection
