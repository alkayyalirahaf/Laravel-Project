@extends('layouts.app')

@php
    $demoFlights = require resource_path('views/shared/demo-flights.php');
@endphp
@php
    $filters = request()->only(['from', 'to', 'depart', 'return', 'passengers', 'adults', 'youth', 'children', 'infants', 'cabin', 'budget', 'stops', 'sort', 'leg']);
    $filters = array_filter($filters, 'is_string');
    $returnLeg = ($filters['leg'] ?? '') === 'return' && !empty($filters['return']);
    $from = strtoupper($filters[$returnLeg ? 'to' : 'from'] ?? '');
    $to = strtoupper($filters[$returnLeg ? 'from' : 'to'] ?? '');
    $flights = $demoFlights->filter(function ($flight) use ($filters, $from, $to) {
        $fares = empty($filters['cabin']) ? $flight['fares'] : array_intersect_key($flight['fares'], [$filters['cabin'] => true]);
        return (!$from || $flight['from'] === $from)
            && (!$to || $flight['to'] === $to)
            && (($filters['stops'] ?? '') === '' || (string) $flight['stops'] === $filters['stops'])
            && $fares
            && (!is_numeric($filters['budget'] ?? null) || min(array_column($fares, 0)) <= (float) $filters['budget']);
    })->sortBy('departure', SORT_REGULAR, ($filters['sort'] ?? '') === 'latest');
@endphp

@section('title'){{ $title }}@endsection

@section('content')
@if(request()->query('trip') === 'multi-city')
    @include('flights.multi-city')
@else
<h1>{{ $title }}</h1>
<p class="notice">Sample schedules and fares for preview. Dates and party size will apply when live inventory is connected.</p>
<x-travel-search :filters="$filters" />
@if(!empty($filters['return']))
    <nav class="subnav section" aria-label="Journey leg">
        <a href="{{ route('flights.search', array_merge($filters, ['leg' => 'outbound'])) }}">Outbound</a>
        <a href="{{ route('flights.search', array_merge($filters, ['leg' => 'return'])) }}">Return</a>
    </nav>
@endif
<form action="{{ route('compare') }}" class="stack section" data-compare>
    @forelse($flights as $flight)
        <x-flight-card :flight="$flight" :preferred-cabin="$filters['cabin'] ?? null">
            <label><input type="checkbox" name="flights[]" value="{{ $flight['id'] }}"> Compare <span class="sr-only">{{ $flight['number'] }}</span></label>
        </x-flight-card>
    @empty
        <p class="panel">No sample flights match those filters. Try AMM to DXB.</p>
    @endforelse
    @if($flights->isNotEmpty()) <div><button class="button">Compare selected flights</button> <span data-compare-status aria-live="polite">Select 2 or 3 flights.</span></div> @endif
</form>
@endif
@endsection
