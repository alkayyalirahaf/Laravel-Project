@extends('layouts.app', ['area' => 'my'])
@section('title', 'Traveler profile')
@section('content')
<h1 class="traveler-profile-title">Traveler profile</h1>
<div class="traveler-profile">
    <section class="panel" aria-labelledby="profile-account-title">
        <h2 id="profile-account-title">Account overview</h2>
        <dl class="traveler-profile-facts">
            <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
            @if($user->created_at)
                <div><dt>Member since</dt><dd>{{ $user->created_at->format('M Y') }}</dd></div>
            @endif
            <div><dt>Credits balance</dt><dd>JOD {{ $user->customer->credit_balance }}</dd></div>
        </dl>
        <a href="{{ route('my.credits') }}">View credits wallet</a>
    </section>
    <form method="POST" class="panel stack" action="{{ route('my.profile.update') }}" aria-labelledby="profile-contact-title">
        @csrf
        @method('PATCH')
        <h2 id="profile-contact-title">Personal details</h2>
        <x-travel-field name="name" label="Full name" :value="$user->name" maxlength="255" autocomplete="name" required />
        <x-travel-field name="phone" label="Phone (optional)" type="tel" :value="$user->customer->phone" maxlength="30" autocomplete="tel" />
        <div><button class="button" type="submit">Save profile</button></div>
    </form>
</div>
@endsection
