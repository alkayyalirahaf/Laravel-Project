@props(['place', 'localImage' => false, 'translateLabels' => false])

@php
    $image = $localImage ? 'assets/landing/catalog/' . strtolower($place['code']) . '.webp' : $place['image'];
    if ($image && str_starts_with($image, 'assets/')) {
        $image = asset($image);
    }
@endphp

<article @class(['destination-card', 'destination-card-text' => !$image])>
    @if($image)
        <img src="{{ $image }}" data-destination-photo alt="" loading="lazy" decoding="async" width="640" height="425">
    @endif
    <div class="destination-copy">
        <span class="destination-code">{{ $place['code'] }}</span>
        @if(isset($place['country']))<p>{{ $translateLabels ? __($place['country']) : $place['country'] }}</p>@endif
        <h3>{{ $translateLabels ? __($place['name']) : $place['name'] }}</h3>
    </div>
</article>
