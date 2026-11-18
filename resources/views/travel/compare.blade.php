@php
    $demoFlights = require resource_path('views/travel/demo-data.php');
@endphp
@php
    $selection = request()->query('flights');
    $ids = is_array($selection) ? array_filter(array_slice($selection, 0, 3), 'is_string') : [];
    $flights = $demoFlights->whereIn('id', $ids);
@endphp
<x-travel-layout :title="$title">
    <h1>Compare flights</h1>
    @if($flights->count() < 2)
        <div class="panel"><p>Choose two or three sample flights to compare.</p><a class="button" href="{{ route('flights.search') }}">Browse flights</a></div>
    @else
        <p class="notice">Sample schedules and fares</p>
        <div class="cards">@foreach($flights as $flight)<x-flight-card :flight="$flight" />@endforeach</div>
        <div class="table-wrap panel section">
            <table><caption>Cabin fares and checked baggage</caption>
                <thead><tr><th scope="col">Cabin</th>@foreach($flights as $flight)<th scope="col">{{ $flight['number'] }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach(['economy', 'business', 'first'] as $class)
                    @php
                        $best = $flights->map(fn ($f) => $f['fares'][$class][0] ?? null)->filter()->min();
                    @endphp
                    <tr><th scope="row">{{ ucfirst($class) }}</th>
                    @foreach($flights as $flight)
                        @php
                            $fare = $flight['fares'][$class] ?? null;
                        @endphp
                        <td @class(['best' => $fare && $fare[0] === $best])>{{ $fare ? "JOD ".$fare[0]." / ".$fare[2]." kg" : 'Unavailable' }}</td>
                    @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-travel-layout>
