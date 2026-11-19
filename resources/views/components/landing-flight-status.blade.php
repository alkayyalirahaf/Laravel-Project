@props(['mode'])

@php
    $flights = require resource_path('views/shared/demo-flights.php');
    $airports = $flights->pluck('origin', 'from')->union($flights->pluck('destination', 'to'))->all();
    $input = array_filter(request()->only(['number', 'origin', 'arrival', 'date']), 'is_string');
    $number = $input['number'] ?? '';
    $origin = $input['origin'] ?? '';
    $arrival = $input['arrival'] ?? '';
    $normalize = fn ($value) => strtoupper(preg_replace('/\s+/', '', trim($value)));
    $results = $flights->filter(fn ($flight) => match ($mode) {
        'number' => $normalize($flight['number']) === $normalize($number),
        'route' => $flight['from'] === $origin && $flight['to'] === $arrival,
        default => false,
    });
@endphp

<p>{{ __('Search sample schedules by flight number or route. Live updates are currently unavailable.') }}</p>
<form method="GET" action="{{ route('home') }}#flight-search" data-flight-status>
    <div class="travel-tool-fields travel-status-fields">
        <x-travel-field name="status_mode" :label="__('Search by')" :value="$mode ?? 'number'"
            :options="['number' => __('Flight number'), 'route' => __('Route')]" />
        <x-travel-field name="date" :label="__('Departure')" type="date" :value="$input['date'] ?? now('Asia/Amman')->toDateString()" required />
        <div data-status-fields="number">
            <x-travel-field name="number" :label="__('Flight number')" :value="$number" placeholder="YT 402" maxlength="12" />
        </div>
        <div class="travel-status-route" data-status-fields="route">
            <x-travel-field name="origin" :label="__('From')" :options="['' => __('Departure airport')] + $airports" :value="$origin" />
            <x-travel-field name="arrival" :label="__('To')" :options="['' => __('Arrival airport')] + $airports" :value="$arrival" />
        </div>
        <button class="button">{{ __('Search schedules') }}</button>
    </div>
</form>
@if(in_array($mode, ['number', 'route'], true))
    <ul class="travel-status-results">
        @forelse($results as $flight)
            <li><strong>{{ $flight['number'] }}</strong> <bdi>{{ $flight['from'] }} → {{ $flight['to'] }} · {{ $flight['departure'] }}–{{ $flight['arrival'] }}</bdi></li>
        @empty
            <li>{{ __('No matching sample flights.') }}</li>
        @endforelse
    </ul>
@endif
