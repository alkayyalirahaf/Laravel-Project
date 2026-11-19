@extends('layouts.app')

@section('title', __('Find your next flight'))
@section('body-class', 'landing')

@section('navigation')
    <a href="#flight-search">{{ __('Flights') }}</a>
    <a href="#destinations">{{ __('Destinations') }}</a>
    <a href="#ai-copilot">{{ __('AI Assistant') }}</a>
    <a href="#how-it-works">{{ __('How It Works') }}</a>
    <x-landing-language />
    <a href="{{ route('login') }}">{{ __('Sign In') }}</a>
    <a class="button" href="#flight-search">{{ __('Find a Flight') }}</a>
@endsection

@php
    $catalog = json_decode(file_get_contents(resource_path('views/shared/destination-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
@endphp

@section('content')
<section class="landing-hero" aria-labelledby="landing-title">
    <div class="landing-hero-image" aria-hidden="true">
        <video class="hero-video" muted loop playsinline preload="none" poster="{{ asset('assets/landing/catalog/jp.webp') }}">
            <source src="{{ asset('assets/landing/hero-travel.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="landing-wrap hero-copy">
        <h1 id="landing-title">{{ __('Your next chapter starts') }} <em>{{ __('above the clouds.') }}</em></h1>
        <p>{{ __('Find your flight, discover new destinations, and plan your journey with a little help from AI.') }}</p>
        <div class="actions">
            <a class="button" href="#flight-search">{{ __('Explore Flights') }} <span aria-hidden="true">↗</span></a>
            <a class="button secondary" href="#ai-copilot">{{ __('Meet Your AI Copilot') }}</a>
        </div>
    </div>
    <svg class="hero-trajectory" viewBox="0 0 420 145" fill="none" aria-hidden="true">
        <path d="M2 142Q220 -18 418 15" stroke="currentColor" stroke-width="1"/>
        <circle cx="418" cy="15" r="2.5" fill="currentColor"/>
    </svg>
</section>

<div class="landing-journey">
    <svg class="journey-path" viewBox="0 0 1000 1000" preserveAspectRatio="none" aria-hidden="true">
        <path pathLength="1" d="M940 0 C930 120 40 100 60 270 S970 350 930 490 S75 525 85 680 S920 755 920 1000"/>
    </svg>

    <div class="landing-wrap search-overlap">
        <x-landing-travel-tools />
    </div>

    <section id="destinations" class="destination-showcase" aria-labelledby="destination-showcase-title">
        <h2 id="destination-showcase-title">{{ __('See the world') }}</h2>
        <div class="landing-wrap">
            <a class="destination-directory-link" href="{{ route('destinations') }}">
                {{ __('Browse all destinations') }} <span aria-hidden="true">&#8599;</span>
            </a>
        </div>
        <div class="destination-track" data-destination-track tabindex="0" role="region" aria-labelledby="destination-showcase-title">
            @foreach(collect($catalog)->pluck('places')->collapse()->keyBy('code')->only(['JP', 'TH', 'LOND', 'PARI', 'AG', 'KY', 'GD', 'KN', 'VC', 'ITO', 'LIR', 'RTB', 'OAX', 'PXM', 'TUS', 'PSP']) as $place)
                <article class="destination-slide">
                    <img src="{{ asset('assets/landing/catalog/' . strtolower($place['code']) . '.webp') }}" alt="" draggable="false" width="640" height="425" loading="lazy" decoding="async">
                    <span class="destination-slide-copy">
                        <span>{{ $place['code'] }}</span>
                        <strong>{{ __($place['name']) }}</strong>
                    </span>
                </article>
            @endforeach
        </div>
    </section>

    <section class="landing-wrap landing-section landing-split" id="ai-copilot" data-reveal>
        <div>
            <h2>{{ __('A world of possibilities.') }} <em>{{ __('One smart conversation.') }}</em></h2>
            <p>{{ __("Tell your AI copilot where you'd like to go. It can help you find flights, compare cabin options, and explore destination tips before you book.") }}</p>
            <a class="button" href="{{ route('assistant') }}">{{ __('Start Planning with AI') }} ↗</a>
        </div>
        <div class="copilot-preview">
            <div class="preview-header"><strong>{{ __('YallaTeer Copilot') }}</strong><span>{{ __('Conversation preview') }}</span></div>
            <p class="chat-question" data-preview-question>{{ __('Help me find a flight to Istanbul.') }}</p>
            <p class="chat-answer" data-preview-answer aria-live="polite">{{ __('Where are you flying from? Share your dates and I can help you compare routes.') }}</p>
            <div class="prompt-list" aria-label="{{ __('Try an example prompt') }}">
                @foreach([
                    'Help me find a flight to Istanbul.' => 'Where are you flying from? Share your dates and I can help you compare routes.',
                    'Compare economy and business class.' => 'Compare the fare, baggage rules, and seat options for the same flight. What route are you considering?',
                    'When is the best season to visit Paris?' => 'Spring and autumn are good starting points for milder weather. What would you like to do in Paris?',
                ] as $prompt => $answer)
                    <button type="button" data-prompt="{{ __($answer) }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ __($prompt) }}</button>
                @endforeach
            </div>
        </div>
    </section>
</div>

<section class="landing-wrap landing-section" id="how-it-works" data-reveal>
    <h2>{{ __('From takeoff plans') }} <em>{{ __('to boarding pass.') }}</em></h2>
    <ol class="booking-steps">
        @foreach([
            ['search', 'Find your flight', 'Compare routes, schedules, and fares.'],
            ['seat', 'Choose your cabin and seat', 'Review fare options and available seats.'],
            ['card', 'Add passenger details and pay', 'Review your itinerary and passenger details before paying.'],
            ['ticket', 'Access your ticket', 'Find it in your bookings when you’re ready to travel.'],
        ] as [$icon, $heading, $description])
            <li>
                <span class="step-number">0{{ $loop->iteration }}</span>
                <x-landing-icon :name="$icon" />
                <h3>{{ __($heading) }}</h3>
                <p>{{ __($description) }}</p>
            </li>
        @endforeach
    </ol>
</section>

<section class="landing-wrap landing-section landing-split" id="cabins" data-reveal>
    <div>
        <h2>{{ __('Choose how') }} <em>{{ __('you fly.') }}</em></h2>
        <p>{{ __('Compare cabin fares, baggage allowances, and seat availability for your selected flight. Options vary by airline and fare.') }}</p>
        <fieldset class="cabin-options">
            <legend class="sr-only">{{ __('Preview a cabin class') }}</legend>
            @foreach(['Economy' => 6, 'Business' => 4, 'First' => 2] as $cabin => $columns)
                <label><input type="radio" name="cabin-preview" value="{{ strtolower($cabin) }}" class="sr-only" @checked($loop->first)><span>{{ __($cabin) }}</span></label>
            @endforeach
        </fieldset>
    </div>
    <div class="cabin-preview">
        @foreach(['Economy' => 6, 'Business' => 4, 'First' => 2] as $cabin => $columns)
            <figure data-cabin="{{ strtolower($cabin) }}" @if(!$loop->first) hidden @endif>
                <figcaption><strong>{{ __($cabin) }}</strong><span>{{ __('Illustrative seat map') }}</span></figcaption>
                <div class="seat-grid" style="--seat-columns: {{ $columns }}" aria-hidden="true">
                    @foreach(range(1, 4) as $row)
                        @foreach(range(1, $columns) as $seat)
                            <span @class(['seat', 'seat-accent' => $row === 2 && $seat === 1])></span>
                            @if($seat === $columns / 2)<span class="seat-aisle">{{ $row }}</span>@endif
                        @endforeach
                    @endforeach
                </div>
                <a class="landing-text-link" href="{{ route('flights.search', ['cabin' => strtolower($cabin)]) }}">{{ __('Compare :cabin flights', ['cabin' => app()->isLocale('ar') ? __($cabin) : strtolower($cabin)]) }} ↗</a>
            </figure>
        @endforeach
    </div>
</section>

<section class="landing-finale" data-reveal>
    <div class="landing-wrap">
        <h2>{{ __('The world is waiting.') }} <em>{{ __('Where to next?') }}</em></h2>
        <a class="button" href="#flight-search">{{ __('Find Your Flight') }} ↗</a>
    </div>
</section>
@endsection

@section('footer-links')
    <p>{{ __('Find a flight worth looking forward to.') }}</p>
    <nav aria-label="{{ __('Footer') }}">
        <a href="#flight-search">{{ __('Flights') }}</a>
        <a href="#destinations">{{ __('Destinations') }}</a>
        <a href="{{ route('assistant') }}">{{ __('AI Assistant') }}</a>
        <a href="{{ route('booking.lookup') }}">{{ __('Booking lookup') }}</a>
        @foreach(['contact' => 'Contact', 'terms' => 'Terms', 'privacy' => 'Privacy'] as $name => $label)
            <a href="{{ route($name) }}">{{ __($label) }}</a>
        @endforeach
    </nav>
@endsection
