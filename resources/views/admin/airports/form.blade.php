@extends('super-admin.management-layout')

@section('admin-title', $title)

@section('admin-content')
<h1>{{ $title }}</h1>

<form action="{{ $formMode === 'create' ? route('admin.airports.store') : route('admin.airports.update', $airport) }}" method="POST" class="panel narrow stack">
    @csrf
    @if($formMode === 'edit')
        @method('PUT')
    @endif

    <label class="field">
        <span>Airport name</span>
        <input name="name" value="{{ old('name', $airport->name) }}" maxlength="150" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>IATA code</span>
        <input name="code" value="{{ old('code', $airport->code) }}" minlength="3" maxlength="3" pattern="[A-Za-z]{3}" required>
        @error('code')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>City</span>
        <select name="city_id" required>
            <option value="">Choose a city</option>
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @selected((string) old('city_id', $airport->city_id) === (string) $city->id)>{{ $city->name }}</option>
            @endforeach
        </select>
        @error('city_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <div class="actions">
        <button class="button" type="submit">{{ $formMode === 'create' ? 'Create airport' : 'Save changes' }}</button>
        <a class="button secondary" href="{{ route('admin.airports') }}">Cancel</a>
    </div>
</form>
@endsection
