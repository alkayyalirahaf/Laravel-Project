@extends('omar.layout', ['title' => 'Write a review'])

@section('omar-content')
    <h1>Write a review</h1>
    <p>{{ $segment->flight->airline->name }} — {{ $segment->flight->flight_number }} — Booking {{ $segment->booking->code }}</p>
    <form method="POST" action="{{ route('my.reviews.store', $segment->id) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="rating">Rating</label>
            <select class="form-select" name="rating" id="rating" required>
                <option value="">Select a rating</option>
                @foreach([1, 2, 3, 4, 5] as $rating)<option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="comment">Your experience</label>
            <textarea class="form-control" name="comment" id="comment" maxlength="5000" rows="5" required>{{ old('comment') }}</textarea>
        </div>
        <p>Submitted reviews are hidden until moderation.</p>
        <button class="btn btn-primary">Submit review</button>
    </form>
@endsection
