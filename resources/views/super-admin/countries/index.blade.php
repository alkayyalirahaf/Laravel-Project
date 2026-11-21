@extends('super-admin.countries.layout')

@section('countries-content')
    <a class="button mb-3" href="{{ route('admin.countries.create') }}">Create country</a>
    <div class="table-wrap panel section">
        <table class="table table-striped align-middle">
            <thead>
                <tr><th>Name</th><th>Code</th><th>Currency</th><th>Language</th><th>Local apps</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($countries as $country)
                    <tr>
                        <td>{{ $country->name }}</td>
                        <td>{{ $country->code }}</td>
                        <td>{{ $country->currency }}</td>
                        <td>{{ $country->language }}</td>
                        <td class="text-break">{{ $country->local_apps ?: '—' }}</td>
                        <td>
                            <a class="button secondary " href="{{ route('admin.countries.edit', $country) }}">Edit</a>
                            @if($country->cities_count === 0)
                                <form class="inline-form" method="POST" action="{{ route('admin.countries.destroy', $country) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button secondary" type="submit">Delete {{ $country->name }}</button>
                                </form>
                            @else
                                <span class="text-muted">Has cities; cannot delete</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No countries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $countries->links('super-admin.pagination') }}
@endsection
