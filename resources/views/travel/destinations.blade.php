@php
    $guides = [
        'JO' => ['Jordan', 'Amman', 'JOD', 'Arabic', 'Queen Alia International (AMM)', 'Airport buses and taxis serve central Amman.', 'Spring and autumn'],
        'AE' => ['United Arab Emirates', 'Dubai', 'AED', 'Arabic', 'Dubai International (DXB)', 'The Dubai Metro serves terminals 1 and 3.', 'November through March'],
    ];
    $country = request()->route('country');
    $selected = $country ? array_intersect_key($guides, [strtoupper($country) => true]) : $guides;
@endphp
<x-travel-layout :title="$title">
    <h1>{{ $title }}</h1>
    <div class="cards">
        @forelse($selected as $code => [$name, $city, $currency, $language, $airport, $transport, $season])
            <article class="panel">
                <h2><a href="{{ route('destinations', $code) }}">{{ $name }}</a></h2>
                <p>Currency · {{ $currency }}</p><p>Language · {{ $language }}</p>
                <h3>{{ $city }}</h3><p>{{ $airport }}</p><p>{{ $transport }}</p><p>Best season · {{ $season }}</p>
            </article>
        @empty <p class="panel">This destination guide is unavailable. <a href="{{ route('destinations') }}">See available guides</a></p> @endforelse
    </div>
</x-travel-layout>
