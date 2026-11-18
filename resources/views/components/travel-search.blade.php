@props(['filters' => []])
<form method="GET" action="{{ route('flights.search') }}" class="panel form-grid" id="flight-search">
    <x-travel-field name="from" label="From" :value="$filters['from'] ?? null" placeholder="AMM" maxlength="3" pattern="[A-Za-z]{3}" />
    <x-travel-field name="to" label="To" :value="$filters['to'] ?? null" placeholder="DXB" maxlength="3" pattern="[A-Za-z]{3}" />
    <x-travel-field name="depart" label="Departure" type="date" :value="$filters['depart'] ?? null" />
    <x-travel-field name="return" label="Return (optional)" type="date" :value="$filters['return'] ?? null" />
    <x-travel-field name="passengers" label="Passengers" type="number" min="1" max="9" :value="$filters['passengers'] ?? 1" />
    <x-travel-field name="cabin" label="Cabin" :value="$filters['cabin'] ?? ''" :options="['' => 'Any cabin', 'economy' => 'Economy', 'business' => 'Business', 'first' => 'First']" />
    <x-travel-field name="budget" label="Max fare (JOD)" type="number" min="0" step="0.01" :value="$filters['budget'] ?? null" />
    @if(request()->routeIs('flights.search'))
        <x-travel-field name="stops" label="Stops" :value="$filters['stops'] ?? ''" :options="['' => 'Any', 0 => 'Nonstop', 1 => '1 stop', 2 => '2 stops']" />
        <x-travel-field name="sort" label="Sort by departure" :value="$filters['sort'] ?? 'earliest'" :options="['earliest' => 'Earliest first', 'latest' => 'Latest first']" />
    @endif
    <div class="wide"><button class="button">Find flights</button></div>
</form>
