@extends('omar.layout', ['title' => 'Travel assistant'])

@section('omar-content')
    <h1>Travel assistant</h1>
    @unless($available)
        <p class="alert alert-warning">AI integration is currently unavailable. You can still <a href="{{ route('flights.search') }}">search flights</a> and <a href="{{ route('compare') }}">compare flights</a>.</p>
    @endunless
    <p>Find and compare use your selected filters and real database flights. The assistant suggests general travel tips. It cannot create a booking or execute a payment.</p>
    <form method="POST" action="{{ route('my.assistant.store') }}" class="card card-body mb-4">
        @csrf
        <fieldset @disabled(!$available)>
            <label class="form-label" for="mode">Mode</label>
            <select class="form-select mb-3" name="mode" id="mode">
                @foreach(['tips' => 'Travel tips', 'find' => 'Find flights using the filters below', 'compare' => 'Compare selected flight IDs below'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('mode') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label class="form-label" for="message">Travel question (do not include passport or payment information)</label>
            <textarea class="form-control mb-3" id="message" name="message" maxlength="2000" rows="3" required>{{ old('message') }}</textarea>
            <details class="mb-3"><summary>Find: flight filters</summary>
                @include('omar.flights.filters', ['optionalFilters' => true])
            </details>
            <details class="mb-3"><summary>Compare: flight IDs from search results</summary>
                @foreach([0, 1, 2] as $index)
                    <label class="form-label" for="flight-{{ $index }}">Flight {{ $index + 1 }} @if($index === 2)(optional)@endif</label>
                    <input class="form-control mb-2" type="number" min="1" id="flight-{{ $index }}" name="flights[]" value="{{ old('flights.'.$index) }}">
                @endforeach
            </details>
            <button class="btn btn-primary">Ask and save conversation</button>
        </fieldset>
    </form>
    <h2>Conversation history</h2>
    <ul class="list-group mb-3">
        @forelse($conversations as $conversation)
            <li class="list-group-item"><a href="{{ route('my.assistant.show', $conversation->id) }}">{{ $conversation->title }}</a> — {{ $conversation->type }} — {{ $conversation->created_at?->format('Y-m-d H:i') }}</li>
        @empty<li class="list-group-item">No saved conversations.</li>@endforelse
    </ul>
    {{ $conversations->links('pagination::bootstrap-5') }}
@endsection
