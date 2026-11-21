@extends('super-admin.management-layout')

@section('admin-title', 'Flights')

@section('admin-content')
    <div class="admin-flights">
        <header class="admin-flights-heading">
            <h1>Flights</h1>
            <span>{{ number_format($flights->total()) }} {{ Str::plural('flight', $flights->total()) }}</span>
        </header>

        <form method="GET" action="{{ route('admin.flights') }}" class="admin-flight-filters">
            <label>
                <span>Flight number</span>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. TK102">
            </label>
            <label>
                <span>Status</span>
                <select name="status">
                    <option value="">All statuses</option>
                    @foreach(['scheduled', 'delayed', 'cancelled', 'completed'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Airline</span>
                <select name="airline_id">
                    <option value="">All airlines</option>
                    @foreach($airlines as $airline)
                        <option value="{{ $airline->id }}" @selected((string) ($filters['airline_id'] ?? '') === (string) $airline->id)>
                            {{ $airline->name }} ({{ $airline->code }})
                        </option>
                    @endforeach
                </select>
            </label>
            <div class="admin-flight-filter-actions">
                <button class="button" type="submit">Apply filters</button>
                <a href="{{ route('admin.flights') }}">Clear</a>
            </div>
        </form>

        <div class="admin-flight-table" role="region" aria-label="Flight results" tabindex="0">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Flight number</th>
                        <th scope="col">Airline</th>
                        <th scope="col">Route</th>
                        <th scope="col">Departure</th>
                        <th scope="col">Arrival</th>
                        <th scope="col">Stops</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($flights as $flight)
                        <tr>
                            <th scope="row">{{ $flight->flight_number }}</th>
                            <td>{{ $flight->airline->name }} <small>{{ $flight->airline->code }}</small></td>
                            <td class="admin-flight-route">{{ $flight->originAirport->code }} <span>&rarr;</span> {{ $flight->destinationAirport->code }}</td>
                            <td><strong>{{ $flight->departure_at->format('H:i') }}</strong><small>{{ $flight->departure_at->format('d M Y') }}</small></td>
                            <td><strong>{{ $flight->arrival_at->format('H:i') }}</strong><small>{{ $flight->arrival_at->format('d M Y') }}</small></td>
                            <td>{{ $flight->stops ?: 'Nonstop' }}</td>
                            <td><span class="admin-flight-status" data-status="{{ $flight->status }}">{{ ucfirst($flight->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="admin-flight-empty">No flights found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $flights->links('super-admin.pagination') }}
    </div>
@endsection
