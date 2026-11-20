@extends('layouts.app')
@section('title', 'Cities')

@section('content')
<div class="stack">
    <h1>Cities</h1>
    <div class="actions"><a class="button" href="{{ route('admin.cities.create') }}">Add city</a></div>
    @error('city')<p class="notice error" role="alert">{{ $message }}</p>@enderror
    <div class="panel table-wrap">
        <table>
            <thead><tr><th>City</th><th>Country</th><th>Best season</th><th>Airports</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($cities as $city)
                    <tr>
                        <th scope="row">{{ $city->name }}</th>
                        <td>{{ $city->country->name }}</td>
                        <td>{{ $city->best_season ?: '—' }}</td>
                        <td>{{ $city->airports_count }}</td>
                        <td>
                            <div class="actions">
                                <a class="button secondary" href="{{ route('admin.cities.edit', $city) }}">Edit</a>
                                @if(!$city->airports_count)
                                    <form method="POST" action="{{ route('admin.cities.destroy', $city) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button secondary" type="submit">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No cities added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $cities->links() }}
</div>
@endsection
