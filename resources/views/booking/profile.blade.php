@extends('layouts.app', ['area' => 'my'])
@section('title', 'Traveler profile')
@section('content')
<h1>Traveler profile</h1>
<form method="POST" class="panel stack narrow" action="{{ route('my.profile.update') }}">
    @csrf
    @method('PATCH')
    <x-travel-field name="name" label="Name" :value="$user->name" maxlength="255" required />
    <p>Email {{ $user->email }}</p>
    <x-travel-field name="phone" label="Phone (optional)" type="tel" :value="$user->customer->phone" maxlength="30" />
    <div><button class="button">Save profile</button></div>
</form>
@endsection
