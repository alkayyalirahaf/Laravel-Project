@extends('layouts.app', ['area' => 'admin', 'navigation' => ['airports' => ['/airports', 'Airports']]])

@section('title', $title)

@section('content')
<div class="bar">
    <h1>{{ $title }}</h1>
    <a class="button" href="{{ route('admin.airports.create') }}">Create airport</a>
</div>

<form action="{{ route('admin.airports') }}" method="GET" class="panel form-grid section">
    <label class="field">
        <span>Search name or IATA code</span>
        <input name="search" value="{{ $filters['search'] }}">
    </label>
    <label class="field">
        <span>City</span>
        <select name="city_id">
            <option value="">All cities</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected((string) $filters['city_id'] === (string) $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>
    </label>
    <div class="actions wide">
        <button class="button" type="submit">Filter</button>
        <a class="button secondary" href="{{ route('admin.airports') }}">Clear</a>
    </div>
</form>

<section class="panel table-wrap section">
    <table>
        <caption>Airports</caption>
        <thead>
            <tr><th>Name</th><th>IATA code</th><th>City</th><th>Actions</th></tr>
        </thead>
        <tbody>
            @forelse($airports as $airport)
                <tr>
                    <td>{{ $airport->name }}</td>
                    <td>{{ $airport->code }}</td>
                    <td>{{ $airport->city->name }}</td>
                    <td>
                        <div class="actions">
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
                <tr><td colspan="4">No airports found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="section">{{ $airports->links() }}</div>
@endsection
