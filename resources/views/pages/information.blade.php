@extends('layouts.app')

@section('title'){{ $title }}@endsection

@section('content')
<div class="prose-page">
    <h1>{{ $title }}</h1>
    @switch($page)
        @case('about')
            <article class="panel"><h2>Our mission</h2><p>Yalla Teer brings flight search, fare comparison, and destination information together so travelers can make informed choices.</p><h2>Our team</h2><p>Our team builds tools for travelers, airline partners, and platform staff.</p></article>
            @break
        @case('faq')
            <div class="stack">
                @foreach([
                    'Where can I find my booking?' => 'Use Booking lookup with your booking code and the last name of a passenger.',
                    'Where can I see baggage allowances?' => 'Open Flight details and review the baggage allowance for the cabin you are considering.',
                    'How do cancellation and credits work?' => 'Cancellation eligibility and credits depend on your fare. Use Contact if you need help with an existing booking.',
                    'How do I select a seat?' => 'Seat selection appears after traveler details. Availability depends on the aircraft and cabin.',
                    'Can I pay online?' => 'Online payment is currently unavailable.',
                ] as $question => $answer)
                    <details class="panel"><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                @endforeach
            </div>
            @break
        @case('terms')
            <article class="panel"><h2>Flight information</h2><p>Airline schedules and fares can change. Confirm your itinerary, baggage allowances, and fare conditions before traveling.</p><h2>Booking support</h2><p>Use the contact form if you need help with an existing reservation.</p><p>Full platform terms are pending publication.</p></article>
            @break
        @case('privacy')
            <article class="panel"><h2>Account and support information</h2><p>Account details support sign-in and booking access. Contact form submissions are stored for staff to respond to your request.</p><h2>Passport data and AI</h2><p>Passport scanning and AI chat are currently unavailable. Keep passport numbers and payment details out of support messages.</p></article>
            @break
        @case('contact')
            <form action="{{ route('contact.store') }}" method="POST" class="panel stack">
                @csrf
                @if(session('status'))
                    <p class="notice" role="status">{{ session('status') }}</p>
                @endif
                <label class="field">
                    <span>Name</span>
                    <input name="name" value="{{ old('name') }}" maxlength="150" required>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
                    @error('email')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Topic</span>
                    <select name="type" required>
                        <option value="question" @selected(old('type') === 'question')>Question</option>
                        <option value="suggestion" @selected(old('type') === 'suggestion')>Suggestion</option>
                        <option value="complaint" @selected(old('type') === 'complaint')>Complaint</option>
                    </select>
                    @error('type')<small class="error">{{ $message }}</small>@enderror
                </label>
                <label class="field">
                    <span>Message</span>
                    <textarea name="message" maxlength="5000" required>{{ old('message') }}</textarea>
                    @error('message')<small class="error">{{ $message }}</small>@enderror
                </label>
                <button class="button" type="submit">Send message</button>
            </form>
            @break
    @endswitch
</div>
@endsection
