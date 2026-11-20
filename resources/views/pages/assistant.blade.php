@extends('layouts.app')

@section('title', 'Travel assistant')

@section('content')
<h1>Travel assistant</h1>
<div class="actions"><a class="button secondary" href="{{ route('flights.search') }}">Find flights</a><a class="button secondary" href="{{ route('compare') }}">Compare</a><a class="button secondary" href="{{ route('destinations') }}">Local tips</a></div>
    @unless($available)
        <p class="notice">AI integration is currently unavailable. You can still <a href="{{ route('flights.search') }}">search flights</a> and <a href="{{ route('compare') }}">compare flights</a>.</p>
    @endunless
    <p>Find and compare use your selected filters and real database flights. The assistant suggests general travel tips. It cannot create a booking or execute a payment.</p>
    <form method="POST" action="{{ route('my.assistant.store') }}" class="panel stack section">
        @csrf
        <fieldset @disabled(!$available)>
            <label class="form-label" for="mode">Mode</label>
            <select class="input-control mb-3" name="mode" id="mode">
                @foreach(['tips' => 'Travel tips', 'find' => 'Find flights using the filters below', 'compare' => 'Compare selected flight IDs below'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('mode') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="form-label" for="message">Travel question (do not include passport or payment information)</label>
            <textarea class="input-control mb-3" id="message" name="message" maxlength="2000" rows="3" required>{{ old('message') }}</textarea>
            <details class="section"><summary>Find: flight filters</summary>
                @include('flights.filters', ['optionalFilters' => true])
            </details>
            <details class="section"><summary>Compare: flight IDs from search results</summary>
                @foreach([0, 1, 2] as $index)
                    <label class="form-label" for="flight-{{ $index }}">Flight {{ $index + 1 }} @if($index === 2)(optional)@endif</label>
                    <input class="input-control mb-2" type="number" min="1" id="flight-{{ $index }}" name="flights[]" value="{{ old('flights.'.$index) }}">
                @endforeach
            </details>
            <button class="button">Ask and save conversation</button>
        </fieldset>
    </form>
    <h2>Conversation history</h2>
    <ul class="stack section">
        @forelse($conversations as $conversation)
            <li class="panel"><a href="{{ route('my.assistant.show', $conversation->id) }}">{{ $conversation->title }}</a> — {{ $conversation->type }} — {{ $conversation->created_at?->format('Y-m-d H:i') }}</li>
        @empty<li class="panel">No saved conversations.</li>@endforelse
    </ul>
    {{ $conversations->links() }}
@endsection
