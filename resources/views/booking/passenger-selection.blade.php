@extends('layouts.app')

@section('title', 'Passengers')

@section('content')
<h1>Passengers</h1>
<div class="passenger-layout">
    <section class="panel passenger-panel">
        <h2>Travelers</h2>
        <form class="stack" method="POST" action="{{ route('book.passengers.store') }}">
            @csrf
            <input type="hidden" name="selection_id" value="{{ $selectionId }}">
            @error('selection')<p class="notice error">{{ $message }}</p>@enderror
            @foreach($travelerTypes as $index => $type)
                <fieldset class="form-grid">
                    <legend>Passenger {{ $loop->iteration }} · {{ ucfirst($type) }}</legend>
                    <x-passport-scan :passenger-index="$index" />
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][first_name]'" label="First / given name" :value="old('passengers.'.$index.'.first_name', $draft['passengers'][$index]['first_name'] ?? null)" maxlength="100" required />
                        @error('passengers.'.$index.'.first_name')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][last_name]'" label="Last / family name" :value="old('passengers.'.$index.'.last_name', $draft['passengers'][$index]['last_name'] ?? null)" maxlength="100" required />
                        @error('passengers.'.$index.'.last_name')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][date_of_birth]'" label="Date of birth" :value="old('passengers.'.$index.'.date_of_birth', $draft['passengers'][$index]['date_of_birth'] ?? null)" type="date" required />
                        @error('passengers.'.$index.'.date_of_birth')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][gender]'" label="Gender" :value="old('passengers.'.$index.'.gender', $draft['passengers'][$index]['gender'] ?? null)" :options="['' => 'Select', 'male' => 'Male', 'female' => 'Female']" required />
                        @error('passengers.'.$index.'.gender')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][nationality]'" label="Nationality" :value="old('passengers.'.$index.'.nationality', $draft['passengers'][$index]['nationality'] ?? null)" maxlength="60" required />
                        @error('passengers.'.$index.'.nationality')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][passport_number]'" label="Passport number" autocomplete="off" maxlength="20" required />
                        @error('passengers.'.$index.'.passport_number')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][passport_country]'" label="Issuing country" :value="old('passengers.'.$index.'.passport_country', $draft['passengers'][$index]['passport_country'] ?? null)" maxlength="60" required />
                        @error('passengers.'.$index.'.passport_country')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][passport_expiry]'" label="Passport expiry" :value="old('passengers.'.$index.'.passport_expiry', $draft['passengers'][$index]['passport_expiry'] ?? null)" type="date" required />
                        @error('passengers.'.$index.'.passport_expiry')<small class="error">{{ $message }}</small>@enderror
                    </div>
                    <div>
                        <x-travel-field :name="'passengers['.$index.'][special_assistance]'" label="Special assistance (optional)" :value="old('passengers.'.$index.'.special_assistance', $draft['passengers'][$index]['special_assistance'] ?? null)" maxlength="255" />
                        @error('passengers.'.$index.'.special_assistance')<small class="error">{{ $message }}</small>@enderror
                    </div>
                </fieldset>
            @endforeach
            @error('passengers')<p class="notice error">{{ $message }}</p>@enderror
            <fieldset class="form-grid">
                <legend>Booking contact</legend>
                <x-travel-field name="contact_email" label="Email address" type="email" :value="$draft['contact_email'] ?? request()->user()->email" maxlength="255" required />
                <x-travel-field name="contact_phone" label="Phone number (optional)" type="tel" :value="$draft['contact_phone'] ?? null" maxlength="30" />
            </fieldset>
            <button class="button" type="submit">Continue to seats</button>
        </form>
        <a href="{{ route('flights.search') }}">Search again</a>
    </section>
    <aside class="panel passenger-panel passenger-summary">
        <h2>Trip summary</h2>
        @foreach($cabins as $label => $cabin)
            <h3>{{ $label }}</h3>
            <dl>
                <div><dt>Route</dt><dd>{{ $cabin->flight->originAirport->code }} &rarr; {{ $cabin->flight->destinationAirport->code }}</dd></div>
                <div><dt>Flight</dt><dd>{{ $cabin->flight->flight_number }}</dd></div>
                <div><dt>Departure</dt><dd>{{ $cabin->flight->departure_at->format('d M Y, H:i') }}</dd></div>
                <div><dt>Cabin</dt><dd>{{ ucfirst($cabin->class) }}</dd></div>
                <div><dt>Current base fare per traveler</dt><dd>JOD {{ $cabin->price }}</dd></div>
            </dl>
        @endforeach
    </aside>
</div>
@endsection
