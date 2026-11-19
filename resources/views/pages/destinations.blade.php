@extends('layouts.app')

@section('title', 'Destinations')
@section('body-class', 'landing')

@php
    $catalog = json_decode(file_get_contents(resource_path('views/shared/destination-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
    $code = strtoupper(request()->route('country') ?? '');
@endphp

@section('content')
<div class="landing-wrap destination-directory">
    <h1>Explore destinations</h1>
    @if($code)
        <p><a href="{{ route('destinations') }}">Browse all destinations</a></p>
    @else
        <nav class="actions destination-index" aria-label="Destination categories">
            @foreach($catalog as $section)
                <a href="#places-{{ $loop->iteration }}">{{ $section['title'] }}</a>
            @endforeach
        </nav>
    @endif
    @php($hasPlaces = false)
    @foreach($catalog as $section)
        @php($places = $code ? array_filter($section['places'], fn ($place) => $place['code'] === $code) : $section['places'])
        @continue(!$places)
        @php($hasPlaces = true)
        <section id="places-{{ $loop->iteration }}" class="directory-section">
            <h2>{{ $section['title'] }}</h2>
            <div class="directory-grid">
                @foreach($places as $place)
                    <x-destination-card :place="$place" :local-image="in_array($place['code'], ['JP', 'LOND', 'TH', 'PARI'], true)" />
                @endforeach
            </div>
        </section>
    @endforeach
    @unless($hasPlaces)
        <p>This destination is unavailable in the catalog.</p>
    @endunless
</div>
@endsection
