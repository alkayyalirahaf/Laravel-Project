@extends('omar.layout', ['title' => 'Find flights'])

@section('omar-content')
    <h1>Find flights</h1>
    <form method="GET" action="{{ route('flights.search') }}" class="card card-body mb-4">
        @include('omar.flights.filters')
        <button class="btn btn-primary" type="submit">Search flights</button>
    </form>
    @if($flights !== null)
        <h2>{{ $filters['leg'] === 'return' ? 'Return flights' : 'Outbound flights' }}</h2>
        @if($filters['leg'] === 'return')
            <a href="{{ route('flights.search', array_diff_key(array_merge($filters, ['leg' => 'outbound']), ['outbound_class_id' => true])) }}">Change outbound selection</a>
        @endif
        <form method="GET" action="{{ route('compare') }}" id="compare-flights"></form>
        @forelse($flights as $flight)
            <article class="card card-body my-3">
                <h3><a href="{{ route('flights.show', $flight) }}">{{ $flight->flight_number }}</a> — {{ $flight->airline->name }}</h3>
                <p>{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}<br>
                    {{ $flight->departure_at->format('Y-m-d H:i') }} → {{ $flight->arrival_at->format('Y-m-d H:i') }} ({{ config('app.timezone') }})</p>
                <label><input type="checkbox" name="flights[]" value="{{ $flight->id }}" form="compare-flights"> Compare this flight</label>
                @foreach($flight->flightClasses as $class)
                    <div class="border-top pt-2 mt-2">
                        <p>{{ ucfirst($class->class) }} — Base fare {{ $class->price }} — {{ $class->available_seats_count }} seats available</p>
                        @if($filters['trip'] === 'round_trip' && $filters['leg'] === 'outbound')
                            <a class="btn btn-outline-primary" href="{{ route('flights.search', array_merge($filters, ['leg' => 'return', 'outbound_class_id' => $class->id])) }}">Select outbound and find return</a>
                        @else
                            <form method="POST" action="{{ route('flights.selection') }}">
                                @csrf
                                @foreach($filters as $key => $value)
                                    @if($value !== null && $key !== 'outbound_class_id')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                                @endforeach
                                <input type="hidden" name="outbound_class_id" value="{{ $filters['leg'] === 'return' ? $filters['outbound_class_id'] : $class->id }}">
                                @if($filters['leg'] === 'return')<input type="hidden" name="return_class_id" value="{{ $class->id }}">@endif
                                <button class="btn btn-primary" type="submit">Confirm flight selection</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </article>
        @empty
            <p class="alert alert-info">No available flights match this search.</p>
        @endforelse
        @if($flights->count())<button class="btn btn-secondary mb-3" form="compare-flights">Compare 2 or 3 flights</button>@endif
        {{ $flights->links('pagination::bootstrap-5') }}
    @endif
@endsection
