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
                    'Can I pay online?' => 'Yes. Payments are processed through Stripe Checkout. Enter your card details on Stripe’s secure page; Yalla Teer does not store them.',
                ] as $question => $answer)
                    <details class="panel"><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                @endforeach
            </div>
            @break
        @case('terms')
            <article class="panel"><h2>Flight information</h2><p>Airline schedules and fares can change. Confirm your itinerary, baggage allowances, and fare conditions before traveling.</p><h2>Booking support</h2><p>Use the contact form if you need help with an existing reservation.</p><p>Full platform terms are pending publication.</p></article>
            @break
        @case('privacy')
            <article class="panel stack">
                <section>
                    <h2>Bookings</h2>
                    <p>We collect the passport and identity details you provide when they are needed to arrange a booking.</p>
                </section>
                <section>
                    <h2>Payments</h2>
                    <p>Card details are entered on Stripe Checkout. We never store your card details.</p>
                </section>
                <section>
                    <h2>Contact messages</h2>
                    <p>Messages sent through our contact form are saved so our team can respond.</p>
                </section>
                <section>
                    <h2>Search and AI</h2>
                    <p>AI features may process text you enter while searching or chatting. Passport photos are never stored.</p>
                </section>
                <section>
                    <h2>Account credits</h2>
                    <p>Your account credits and balance changes are kept in a transaction ledger.</p>
                </section>
            </article>
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
