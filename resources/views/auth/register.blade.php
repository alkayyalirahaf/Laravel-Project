@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="narrow">
    <h1>Create your account</h1>
    <form method="POST" data-preview-form class="panel stack">
        <p class="notice">Preview only. Use sample information. Submission is unavailable.</p>
        <x-travel-field name="name" label="Full name" autocomplete="name" required />
        <x-travel-field name="email" label="Email" type="email" autocomplete="email" required />
        <x-travel-field name="phone" label="Phone" type="tel" autocomplete="tel" />
        <x-travel-field name="password" label="Password (at least 8 characters)" type="password" disabled autocomplete="new-password" minlength="8" required />
        <x-travel-field name="password_confirmation" label="Confirm password" type="password" disabled autocomplete="new-password" required />
        <button class="button" disabled>Preview Create account</button><p data-preview-status role="status"></p>
    </form>
    <a href="{{ route('login') }}">Already have an account?</a>
</div>
@endsection
