@extends('super-admin.management-layout')

@section('admin-title', 'Flights')

@section('admin-content')
    <h1>Flights</h1>

    <form method="GET" action="{{ route('admin.flights') }}" class="row g-2 mb-3">
        <div class="col-md-4">
            <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search flight number">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach(['scheduled', 'delayed', 'cancelled', 'completed'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="airline_id">
                <option value="">All airlines</option>
                @foreach($airlines as $airline)
                    <option value="{{ $airline->id }}" @selected((string) ($filters['airline_id'] ?? '') === (string) $airline->id)>
                        {{ $airline->name }} ({{ $airline->code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Filter</button></div>
        <div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('admin.flights') }}">Clear</a></div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
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
                        <td>{{ $flight->airline->name }} ({{ $flight->airline->code }})</td>
                        <td>{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}</td>
                        <td>{{ $flight->departure_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $flight->arrival_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $flight->stops }}</td>
                        <td><span class="badge text-bg-secondary">{{ ucfirst($flight->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No flights found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $flights->links('pagination::bootstrap-5') }}
@endsection
