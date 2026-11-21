@php
    $search = validator(request()->query(), [
        'legs' => ['required', 'array', 'list', 'min:2', 'max:5'],
        'legs.*' => ['required', 'array:from,to,depart'],
        'legs.*.from' => ['required', 'alpha:ascii', 'size:3'],
        'legs.*.to' => ['required', 'alpha:ascii', 'size:3', 'different:legs.*.from'],
        'legs.*.depart' => ['required', 'date_format:Y-m-d'],
    ]);
@endphp
<h1 class="flight-page-title">{{ __('Multi-city results') }}</h1>
<p><a href="{{ route('home') }}#flight-search">{{ __('Back to flight search') }}</a></p>
@if($search->fails())
    <p class="notice error">{{ __('Enter a departure airport, a different arrival airport, and a date for each flight.') }}</p>
@else
    <p class="notice">{{ __('Sample schedules are shown for each leg. Multi-city booking as a single ticket is not available.') }}</p>
    @foreach($search->validated()['legs'] as $leg)
        @php
            $matches = $demoFlights->filter(fn ($flight) => $flight['from'] === strtoupper($leg['from'])
                && $flight['to'] === strtoupper($leg['to'])
                && (empty($filters['cabin']) || isset($flight['fares'][$filters['cabin']])));
        @endphp
        <section class="stack section">
            <h2>{{ __('Flight :number', ['number' => $loop->iteration]) }} <bdi>{{ strtoupper($leg['from']) }} → {{ strtoupper($leg['to']) }} · {{ $leg['depart'] }}</bdi></h2>
            @forelse($matches as $flight)
                <x-flight-card :flight="$flight" />
            @empty
                <p class="panel">{{ __('No matching sample flights.') }}</p>
            @endforelse
        </section>
    @endforeach
@endif
