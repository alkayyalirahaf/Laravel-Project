@extends('layouts.app')

@section('title', __('Destinations'))
@section('body-class', 'landing')

@php
    $catalog = json_decode(file_get_contents(resource_path('views/shared/destination-catalog.json')), true, flags: JSON_THROW_ON_ERROR);
@endphp

@section('content')
<div class="landing-wrap destination-directory">
    <h1>{{ __('Explore destinations') }}</h1>
    @foreach($catalog as $category)
        <section class="directory-section">
            <h2>{{ __($category['title']) }}</h2>
            <div class="directory-grid">
                @foreach($category['places'] as $place)
                    <x-destination-card :place="$place"
                        :local-image="is_file(public_path('assets/landing/catalog/' . strtolower($place['code']) . '.webp'))"
                        translate-labels />
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
