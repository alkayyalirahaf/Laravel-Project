@extends('layouts.app')
@section('title', $city->exists ? 'Edit city' : 'Add city')

@section('content')
<div class="stack">
    <a href="{{ route('admin.cities') }}">← Back to cities</a>
    <h1>{{ $city->exists ? 'Edit city' : 'Add city' }}</h1>
    @if(!$countries)
        <p class="notice">Add a country before creating a city. <a href="{{ route('admin.countries') }}">View countries</a></p>
    @endif
    <form class="panel stack" method="POST" action="{{ $city->exists ? route('admin.cities.update', $city) : route('admin.cities.store') }}">
        @csrf
        @if($city->exists) @method('PATCH') @endif
        <div class="form-grid">
            <x-travel-field name="name" label="City name" :value="$city->name" maxlength="100" required />
            <x-travel-field name="country_id" label="Country" :value="$city->country_id" :options="['' => 'Select a country'] + $countries" required />
            <x-travel-field name="best_season" label="Best season (optional)" :value="$city->best_season" maxlength="100" />
            <x-travel-field name="airport_transport" label="Airport transport (optional)" type="textarea" :value="$city->airport_transport" maxlength="10000" />
            <x-travel-field name="tips" label="Travel tips (optional)" type="textarea" :value="$city->tips" maxlength="10000" />
        </div>
        <div><button type="submit" class="button" @disabled(!$countries)>Save city</button></div>
    </form>
</div>
@endsection
