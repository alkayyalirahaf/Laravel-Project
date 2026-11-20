@extends('layouts.app')

@section('title', 'Find flights')

@section('content')
<h1>Find flights</h1>
    <x-landing-search />
    @if($flights !== null)
        <form method="GET" action="{{ route('flights.search') }}" class="search-refinements">
            @foreach($filters as $key => $value)
                @if($value !== null && !in_array($key, ['sort', 'min_price', 'budget']))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <x-travel-field name="min_price" label="Minimum fare" type="number" min="0" step="0.01" :value="$filters['min_price'] ?? null" />
            <x-travel-field name="budget" label="Maximum fare" type="number" min="0" step="0.01" :value="$filters['budget'] ?? null" />
            <x-travel-field name="sort" label="Sort flights" :value="$filters['sort']" :options="['departure_asc' => 'Earliest departure', 'departure_desc' => 'Latest departure', 'price_asc' => 'Lowest price', 'price_desc' => 'Highest price']" />
            <button class="button" type="submit">Apply filters</button>
        </form>
    @endif
    @if($flights !== null)
        <h2>{{ $filters['leg'] === 'return' ? 'Return flights' : 'Outbound flights' }}</h2>
        @if(!isset($filters['adults']))
            <p class="notice">Choose the adult, youth and child counts in the search form and search again before selecting flights.</p>
        @endif
        @if($filters['leg'] === 'return')
            <a href="{{ route('flights.search', array_diff_key(array_merge($filters, ['leg' => 'outbound']), ['outbound_class_id' => true])) }}">Change outbound selection</a>
        @endif
        <form method="GET" action="{{ route('compare') }}" id="compare-flights" data-compare>
            <p data-compare-status role="status">Select 2 or 3 flights.</p>
        </form>
        <div class="stack section">
        @forelse($flights as $flight)
            <article class="panel stack">
                <x-flight-card :flight="$flight" :preferred-cabin="$filters['cabin'] ?? null">
                    <label><input type="checkbox" name="flights[]" value="{{ $flight->id }}" form="compare-flights"> Compare this flight</label>
                </x-flight-card>
                @foreach($flight->flightClasses as $class)
                    <div class="border-top pt-2 mt-2">
                        <p>{{ ucfirst($class->class) }} — Base fare {{ $class->price }} — {{ $class->available_seats_count }} seats available</p>
                        @if(isset($filters['adults']))
                        @if($filters['trip'] === 'round_trip' && $filters['leg'] === 'outbound')
                            <a class="button secondary" href="{{ route('flights.search', array_merge($filters, ['leg' => 'return', 'outbound_class_id' => $class->id])) }}">Select outbound and find return</a>
                        @else
                            <form method="POST" action="{{ route('flights.selection') }}">
                                @csrf
                                @foreach($filters as $key => $value)
                                    @if($value !== null && $key !== 'outbound_class_id')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                                @endforeach
                                <input type="hidden" name="outbound_class_id" value="{{ $filters['leg'] === 'return' ? $filters['outbound_class_id'] : $class->id }}">
                                @if($filters['leg'] === 'return')<input type="hidden" name="return_class_id" value="{{ $class->id }}">@endif
                                <button class="button" type="submit">Confirm flight selection</button>
                            </form>
                        @endif
                        @endif
                    </div>
                @endforeach
            </article>
        @empty
            <p class="notice">No available flights match this search.</p>
        @endforelse
        </div>
        @if($flights->count())<button class="button secondary mb-3" form="compare-flights">Compare 2 or 3 flights</button>@endif
        {{ $flights->links() }}
    @endif
@endsection
