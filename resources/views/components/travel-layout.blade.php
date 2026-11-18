@props(['title', 'area' => null, 'navigation' => []])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Yalla Teer</title>
    <link rel="icon" href="{{ asset('assets/YallaTeer-Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="wrap bar">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ asset('assets/YallaTeer-Logo-02.png') }}" width="48" height="36" alt="">
                <span>Yalla Teer</span>
            </a>
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" hidden>Menu</button>
            <nav id="primary-nav" aria-label="Main navigation">
                <a href="{{ route('flights.search') }}">Flights</a>
                <a href="{{ route('destinations') }}">Destinations</a>
                <a href="{{ route('booking.lookup') }}">Booking lookup</a>
                <a href="{{ route('my.bookings') }}">My bookings</a>
                <a class="button secondary" href="{{ route('login') }}">Sign in</a>
            </nav>
        </div>
    </header>
    <main id="main" class="wrap main">
        @if(session('status')) <p class="notice" role="status">{{ session('status') }}</p> @endif
        @if(isset($errors) && $errors->any()) <p class="notice error" role="alert">Check the highlighted fields and try again.</p> @endif
        @if($area)
            <nav class="subnav" aria-label="{{ $area === 'my' ? 'Your account' : 'Management' }}">
                @php
                    $links = $area === 'my'
                        ? ['bookings' => ['/bookings', 'My Bookings'], 'credits' => ['/credits', 'Credits Wallet'], 'profile' => ['/profile', 'Profile']]
                        : $navigation;
                @endphp
                @foreach($links as $key => [$path, $label])
                    @unless(str_contains($path, '{'))
                        <a @if(request()->routeIs("$area.$key")) aria-current="page" @endif href="{{ route("$area.$key") }}">{{ $label }}</a>
                    @endunless
                @endforeach
            </nav>
        @endif
        {{ $slot }}
    </main>
    <footer class="site-footer">
        <div class="wrap bar">
            <a class="brand" href="{{ route('home') }}"><img src="{{ asset('assets/YallaTeer-Logo-02.png') }}" width="48" height="36" alt=""> Yalla Teer</a>
            <nav aria-label="Footer">
                @foreach(['about' => 'About', 'faq' => 'FAQ', 'terms' => 'Terms', 'privacy' => 'Privacy', 'contact' => 'Contact', 'assistant' => 'AI assistant'] as $name => $label)
                    <a href="{{ route($name) }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </footer>
</body>
</html>
