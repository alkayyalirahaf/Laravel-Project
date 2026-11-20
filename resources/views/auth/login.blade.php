@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>{{ $title }}</h1>
    @php($loginAction = $action ?? (\Illuminate\Support\Facades\Route::has('login.store') ? route('login.store') : null))
    @if($loginAction)
    <form action="{{ $loginAction }}" method="POST" class="panel stack" onsubmit="if (this.dataset.submitting === 'true') return false; this.dataset.submitting = 'true'; const button = this.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Signing in…';">
        @csrf
        <x-travel-field name="email" label="Email" type="email" autocomplete="username" required />
        <x-travel-field name="password" label="Password" type="password" autocomplete="current-password" required />
        <button class="button" type="submit">Sign in</button>
    </form>
    @else
        <p class="notice" role="status">Sign-in is temporarily unavailable. Please try again later.</p>
    @endif
    @if(request()->routeIs('login'))
        <div class="actions section">
            <a class="button secondary" href="{{ route('register') }}">Create an account</a>
            <a class="button secondary" href="{{ route('airline.login') }}">Airline staff</a>
            <a class="button secondary" href="{{ route('admin.login') }}">Platform staff</a>
        </div>
    @endif
</div>
@endsection
