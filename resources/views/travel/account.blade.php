@php
    if (!app()->isLocal() || auth()->check()) {
        abort_unless(auth()->user()?->customer, 403);
    }

    $code = request()->route('code') ?? 'YTDEMO';
    $filter = request()->query('status', 'upcoming');
@endphp
<x-travel-layout :title="$title" area="my">
    <h1>{{ $title }}</h1>
    <p class="notice">Sample account. Changes made here are for preview only.</p>
    @switch($screen)
        @case('bookings')
            <nav class="subnav" aria-label="Booking status">
                @foreach(['upcoming', 'completed', 'cancelled'] as $status)
                    <a href="{{ route('my.bookings', ['status' => $status]) }}" @if($filter === $status) aria-current="page" @endif>{{ ucfirst($status) }}</a>
                @endforeach
            </nav>
            @if(in_array($filter, ['upcoming', 'completed'], true))
                <article class="panel"><h2><a href="{{ route('my.booking', $filter === 'completed' ? 'YTPAST' : 'YTDEMO') }}">Amman → Dubai</a></h2><p>{{ $filter === 'completed' ? 'Completed' : 'Upcoming' }} · YT 402</p></article>
            @else <p class="panel">No bookings in this category.</p> @endif
            @break
        @case('booking')
            <x-booking-summary :code="$code" />
            <div class="actions section">
                <a class="button" href="{{ route('my.review', $code) }}">Write a review</a>
                <a href="{{ route('contact') }}">Get help with changes or cancellations</a>
            </div>
            @break
        @case('review')
            <form class="panel narrow stack" method="post" data-preview-form>
                <h2>Amman → Dubai</h2>
                <x-travel-field name="rating" label="Rating" :options="[5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']" required />
                <x-travel-field name="comment" label="Comment" type="textarea" maxlength="4000" required />
                <button class="button" disabled>Preview review</button>
                <p data-preview-status role="status"></p>
            </form>
            @break
        @case('credits')
            <div class="panel"><p class="eyebrow">Sample balance</p><h2>JOD 25.00</h2></div>
            <section class="panel table-wrap section"><table><caption>Credit activity</caption>
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th></tr></thead>
                <tbody><tr><td>10 Nov</td><td>Sample credit</td><td>+ JOD 30.00</td></tr><tr><td>11 Nov</td><td>Sample redemption</td><td>− JOD 5.00</td></tr></tbody>
            </table></section>
            @break
        @case('profile')
            <form method="post" data-preview-form class="panel narrow stack">
                <x-travel-field name="name" label="Name" value="Sample Traveler" required />
                <x-travel-field name="email" label="Email" type="email" value="traveler@example.test" required />
                <x-travel-field name="phone" label="Phone" type="tel" />
                <x-travel-field name="password" label="New password" type="password" autocomplete="new-password" disabled />
                <button class="button" disabled>Preview changes</button>
                <p data-preview-status role="status"></p>
            </form>
            @break
    @endswitch
</x-travel-layout>
