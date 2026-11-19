@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>{{ $title }}</h1>
    <p>Try booking code <strong>YTDEMO</strong> and last name <strong>Traveler</strong>. Use sample details only.</p>
    <form method="post" class="panel stack" data-preview-form data-lookup>
        <x-travel-field name="code" label="Booking code" maxlength="12" required />
        <x-travel-field name="last_name" label="Passenger’s last name" required />
        <button class="button" disabled>Find sample booking</button>
        <p data-preview-status role="status"></p>
        <a data-lookup-result href="{{ route('my.booking', 'YTDEMO') }}" hidden>Open sample booking</a>
    </form>
</div>
@endsection
