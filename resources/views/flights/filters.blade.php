<div class="form-grid section">
    @foreach(['from' => 'Departure airport', 'to' => 'Arrival airport'] as $name => $label)
        <div class="col-md-6">
            <label class="form-label" for="{{ $name }}">{{ $label }}</label>
            <select class="input-control" id="{{ $name }}" name="{{ $name }}">
                <option value="">Select airport, or choose a city below</option>
                @foreach($airports as $airport)
                    <option value="{{ $airport->code }}" @selected(old($name, $filters[$name] ?? '') === $airport->code)>{{ $airport->code }} — {{ $airport->name }} ({{ $airport->city->name }})</option>
                @endforeach
            </select>
        </div>
    @endforeach
    @foreach(['origin_city_id' => 'Departure city', 'destination_city_id' => 'Arrival city'] as $name => $label)
        <div class="col-md-6">
            <label class="form-label" for="{{ $name }}">{{ $label }} (optional alternative to airport)</label>
            <select class="input-control" id="{{ $name }}" name="{{ $name }}">
                <option value="">Select city</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" @selected((string) old($name, $filters[$name] ?? '') === (string) $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
    <div class="col-md-4">
        <label class="form-label" for="trip">Trip</label>
        <select class="input-control" id="trip" name="trip">
            @foreach(['one_way' => 'One way', 'round_trip' => 'Round trip'] as $value => $label)
                <option value="{{ $value }}" @selected(old('trip', $filters['trip'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @foreach(['depart' => 'Departure date', 'return' => 'Return date (round trip only)'] as $name => $label)
        <div class="col-md-4">
            <label class="form-label" for="{{ $name }}">{{ $label }}</label>
            <input class="input-control" type="date" id="{{ $name }}" name="{{ $name }}" min="{{ now('Asia/Amman')->toDateString() }}" value="{{ old($name, $filters[$name] ?? '') }}" @required($name === 'depart' && empty($optionalFilters))>
        </div>
    @endforeach
    <div class="col-md-3">
        <label class="form-label" for="cabin">Cabin</label>
        <select class="input-control" id="cabin" name="cabin">
            <option value="">Any cabin</option>
            @foreach(['economy', 'business', 'first'] as $cabin)
                <option value="{{ $cabin }}" @selected(old('cabin', $filters['cabin'] ?? '') === $cabin)>{{ ucfirst($cabin) }}</option>
            @endforeach
        </select>
    </div>
    @foreach(['adults' => 'Adults', 'youth' => 'Youth', 'children' => 'Children', 'infants' => 'Infants'] as $name => $label)
        <div class="col-md-3">
            <label class="form-label" for="{{ $name }}">{{ $label }}</label>
            <input class="input-control" type="number" id="{{ $name }}" name="{{ $name }}"
                   min="{{ $name === 'adults' ? 1 : 0 }}" max="{{ $name === 'infants' ? 0 : 9 }}"
                   value="{{ old($name, $filters[$name] ?? ($name === 'adults' ? 1 : 0)) }}" required
                   @if($name === 'infants') readonly aria-describedby="infant-policy-note" @endif>
        </div>
    @endforeach
    <p id="infant-policy-note" class="text-muted">Up to 9 seats per search. Infant searches are unavailable until infant seat rules are confirmed.</p>
    @foreach(['min_price' => 'Minimum base fare', 'budget' => 'Maximum base fare'] as $name => $label)
        <div class="col-md-3">
            <label class="form-label" for="{{ $name }}">{{ $label }}</label>
            <input class="input-control" type="number" id="{{ $name }}" name="{{ $name }}" min="0" step="0.01" value="{{ old($name, $filters[$name] ?? '') }}">
        </div>
    @endforeach
    <div class="col-md-6">
        <label class="form-label" for="sort">Sort</label>
        <select class="input-control" id="sort" name="sort">
            @foreach(['departure_asc' => 'Departure: earliest', 'departure_desc' => 'Departure: latest', 'price_asc' => 'Price: lowest', 'price_desc' => 'Price: highest'] as $value => $label)
                <option value="{{ $value }}" @selected(old('sort', $filters['sort'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<p class="text-muted">Times use {{ config('app.timezone') }}. Prices are per-seat base fares. Final totals are confirmed during booking.</p>
