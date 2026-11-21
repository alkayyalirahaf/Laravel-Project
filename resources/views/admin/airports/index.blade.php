@extends('super-admin.management-layout')

@section('admin-title', $title)

@section('admin-content')
<div class="admin-flights admin-airports">
<header class="admin-flights-heading admin-airports-heading">
    <h1>{{ $title }}</h1>
    <span>{{ number_format($airports->total()) }} {{ Str::plural('airport', $airports->total()) }}</span>
    <a class="button" href="{{ route('admin.airports.create') }}">Create airport</a>
</header>

<form action="{{ route('admin.airports') }}" method="GET" class="admin-flight-filters">
    <label>
        <span>Search name or IATA code</span>
        <input type="search" name="search" value="{{ $filters['search'] }}" placeholder="e.g. Cairo or CAI">
    </label>
    <label>
        <span>City</span>
        <select name="city_id">
            <option value="">All cities</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected((string) $filters['city_id'] === (string) $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>
    </label>
    <div class="admin-flight-filter-actions">
        <button class="button" type="submit">Apply filters</button>
        <a href="{{ route('admin.airports') }}">Clear</a>
    </div>
</form>

<div class="admin-flight-table" role="region" aria-label="Airport results" tabindex="0">
    <table>
        <thead>
            <tr><th scope="col">Name</th><th scope="col">IATA code</th><th scope="col">City</th><th scope="col">Actions</th></tr>
        </thead>
        <tbody>
            @forelse($airports as $airport)
                <tr>
                    <th scope="row">{{ $airport->name }}</th>
                    <td><span class="admin-flight-status">{{ $airport->code }}</span></td>
                    <td>{{ $airport->city->name }}</td>
                    <td>
                        <div class="admin-airport-actions">
                            <a href="{{ route('admin.airports.edit', $airport) }}">Edit</a>
                            <form action="{{ route('admin.airports.destroy', $airport) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button class="button secondary" type="submit" onclick="return confirm('Delete this airport?')">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="admin-flight-empty">No airports found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $airports->links('super-admin.pagination') }}
</div>
@endsection
