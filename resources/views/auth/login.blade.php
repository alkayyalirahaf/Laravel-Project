@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>{{ $title }}</h1>
    <form action="{{ $action }}" method="POST" class="panel stack">
        @csrf
        <x-travel-field name="email" label="Email" type="email" autocomplete="username" required />
        <x-travel-field name="password" label="Password" type="password" autocomplete="current-password" required />
        <button class="button" type="submit">Sign in</button>
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
