@props(['flight'])
<article class="panel flight-card">
    <div>
        <span class="badge">{{ $flight['airline'] }} · {{ $flight['number'] }}</span>
        <h2>{{ $flight['from'] }} → {{ $flight['to'] }}</h2>
        <p>{{ $flight['departure'] }} → {{ $flight['arrival'] }} · {{ $flight['duration'] }} · {{ $flight['stops'] ? $flight['stops'].' stops' : 'Nonstop' }}</p>
        <p>From JOD {{ min(array_column($flight['fares'], 0)) }}</p>
    </div>
    <div class="actions">
        {{ $slot }}
        <a class="button secondary" href="{{ route('flights.show', $flight['id']) }}">Flight details</a>
    </div>
</article>
