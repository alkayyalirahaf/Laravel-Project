@extends('super-admin.countries.layout')

@section('countries-content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <h1 class="mb-0">Countries</h1>
        <a class="btn btn-primary px-4 py-2" href="{{ route('admin.countries.create') }}">Add country</a>
    </div>
    <div class="table-responsive">
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
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.countries.edit', $country) }}">Edit</a>
                            @if($country->cities_count === 0)
                                <form class="d-inline" method="POST" action="{{ route('admin.countries.destroy', $country) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
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
    {{ $countries->links('pagination::bootstrap-5') }}
@endsection
