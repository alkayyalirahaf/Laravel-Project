@extends('layouts.app')

@section('title', __('Destinations'))
@section('body-class', 'landing')

@section('content')
<div class="landing-wrap destination-directory">
    <h1>{{ __('Explore destinations') }}</h1>
    @forelse($countries as $country)
        <section class="directory-section">
            <h2>{{ $country->name }}</h2>
            @if($country->cities->isEmpty())
                <p>No city information is available yet.</p>
            @else
                <div class="directory-grid">
                    @foreach($country->cities as $city)
                        <article class="panel">
                            <h3>{{ $city->name }}</h3>
                            @if($city->best_season)<p><strong>Best season:</strong> {{ $city->best_season }}</p>@endif
                            @if($city->airport_transport)<p><strong>Airport transport:</strong> {{ $city->airport_transport }}</p>@endif
                            @if($city->tips)<p><strong>Travel tips:</strong> {{ $city->tips }}</p>@endif
                            @if($city->airports->isNotEmpty())
                                <h4>Airports</h4>
                                <ul>
                                    @foreach($city->airports as $airport)
                                        <li>{{ $airport->name }} ({{ $airport->code }})</li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    @empty
        <p>Destination information will be available soon.</p>
    @endforelse
</div>
@endsection
