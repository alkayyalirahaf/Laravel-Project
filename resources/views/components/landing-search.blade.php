@php
    $flights = require resource_path('views/shared/demo-flights.php');
    $airports = $flights->pluck('origin', 'from')->union($flights->pluck('destination', 'to'))
        ->sortKeys()->map(fn ($name, $code) => "$code · $name")->all();
    $airports += ['CDG' => 'CDG · Paris Charles de Gaulle', 'IST' => 'IST · Istanbul Airport'];
    ksort($airports);
@endphp
<form class="landing-search" id="flight-search" method="GET" action="{{ route('flights.search') }}"
    data-traveler-one="{{ __('1 traveler') }}" data-traveler-many="{{ __(':count travelers') }}"
    data-swap-error="{{ __('Choose a different arrival airport.') }}">
    <fieldset class="trip-options">
        <legend class="sr-only">{{ __('Journey type') }}</legend>
        <label><input class="sr-only" type="radio" name="trip" value="round-trip" checked><span>{{ __('Round Trip') }}</span></label>
        <label><input class="sr-only" type="radio" name="trip" value="one-way"><span>{{ __('One Way') }}</span></label>
    </fieldset>
    <div class="landing-search-fields">
        <x-travel-field name="from" :label="__('From')" :options="['' => __('Departure airport')] + $airports" required />
        <button type="button" class="swap-airports" aria-label="{{ __('Swap departure and arrival airports') }}">⇄</button>
        <x-travel-field name="to" :label="__('To')" :options="['' => __('Arrival airport')] + $airports" required />
        <x-travel-field name="depart" :label="__('Departure')" type="date" required />
        <div class="return-date"><x-travel-field name="return" :label="__('Return')" type="date" /></div>
        <details class="landing-travelers">
            <summary><span>{{ __('Travelers') }}</span><strong data-traveler-summary>{{ __('1 traveler') }}</strong></summary>
            <div class="traveler-popover">
                @foreach(['adults' => 'Adults', 'youth' => 'Youth (12-16)', 'children' => 'Children (2-12)', 'infants' => 'Infants (under 2)'] as $name => $label)
                    @php($counts = range($name === 'adults' ? 1 : 0, 9))
                    <x-travel-field :name="$name" :label="__($label)" :options="array_combine($counts, $counts)" />
                @endforeach
            </div>
        </details>
        <x-travel-field name="cabin" :label="__('Cabin class')" :options="['' => __('Any cabin'), 'economy' => __('Economy'), 'business' => __('Business'), 'first' => __('First')]" />
        <button class="button search-submit">{{ __('Search Flights') }} <span aria-hidden="true">↗</span></button>
    </div>
</form>
