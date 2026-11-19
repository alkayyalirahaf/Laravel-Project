@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>{{ $title }}</h1>
    <form method="POST" data-preview-form class="panel stack">
        <p class="notice">Preview only. Use sample information. Submission is unavailable.</p>
        <x-travel-field name="email" label="Email" type="email" autocomplete="username" required />
        <x-travel-field name="password" label="Password" type="password" disabled autocomplete="current-password" required />
        <button class="button" disabled>Preview Sign in</button><p data-preview-status role="status"></p>
    </form>
    @if(request()->routeIs('login'))
        <div class="actions section">
            <a href="{{ route('register') }}">Create an account</a>
            <a href="{{ route('airline.login') }}">Airline staff</a>
            <a href="{{ route('admin.login') }}">Platform staff</a>
        </div>
    @endif
</div>
@endsection
