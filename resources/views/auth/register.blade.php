@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>Create your account</h1>
    <form action="{{ route('register.store') }}" method="POST" class="panel stack" onsubmit="if (this.dataset.submitting === 'true') return false; this.dataset.submitting = 'true'; const button = this.querySelector('button[type=submit]'); button.disabled = true; button.textContent = 'Creating account…';">
        @csrf
        <x-travel-field name="name" label="Full name" autocomplete="name" required />
        <x-travel-field name="email" label="Email" type="email" autocomplete="email" required />
        <x-travel-field name="phone" label="Phone" type="tel" autocomplete="tel" />
        <x-travel-field name="password" label="Password (12+ characters, upper/lowercase, number, symbol)" type="password" autocomplete="new-password" minlength="12" required />
        <x-travel-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" minlength="12" required />
        <button class="button" type="submit">Create account</button>
    </form>
    <div class="actions section">
        <a class="button secondary" href="{{ route('login') }}">Already have an account?</a>
    </div>
</div>
@endsection
