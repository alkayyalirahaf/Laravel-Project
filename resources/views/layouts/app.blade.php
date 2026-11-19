<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['ar', 'fa', 'he', 'ur'], true) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Travel') | Yalla Teer</title>
    <link rel="icon" href="{{ asset('assets/YallaTeer-Logo-02.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body-class')">
    <a class="skip" href="#main">{{ __('Skip to content') }}</a>
    <header class="site-header">
        <div class="wrap bar">
            <a class="brand" href="{{ route('home') }}">
                <img src="{{ asset('assets/YallaTeer-Logo-02.png') }}" width="112" height="72" alt="{{ __('YallaTeer home') }}">
            </a>
            <button class="menu-toggle" type="button" aria-label="{{ __('Open navigation') }}" data-open-label="{{ __('Open navigation') }}" data-close-label="{{ __('Close navigation') }}" aria-expanded="false" aria-controls="primary-nav" hidden>
                <span class="menu-icon" aria-hidden="true"><span></span><span></span><span></span></span>
            </button>
            <nav id="primary-nav" aria-label="{{ __('Main navigation') }}">
                @section('navigation')
                <a href="{{ route('flights.search') }}">{{ __('Flights') }}</a>
                <a href="{{ route('destinations') }}">{{ __('Destinations') }}</a>
                <a href="{{ route('booking.lookup') }}">{{ __('Booking lookup') }}</a>
                <a href="{{ route('my.bookings') }}">My bookings</a>
                <a class="button secondary" href="{{ route('login') }}">Sign in</a>
                @show
            </nav>
        </div>
    </header>
    <main id="main" class="wrap main">
        @if(session('status')) <p class="notice" role="status">{{ session('status') }}</p> @endif
        @if(isset($errors) && $errors->any()) <p class="notice error" role="alert">{{ __('Check the highlighted fields and try again.') }}</p> @endif
        @if(!empty($area))
            <nav class="subnav" aria-label="{{ $area === 'my' ? 'Your account' : 'Management' }}">
                @php
                    $links = $area === 'my'
                        ? ['bookings' => ['/bookings', 'My Bookings'], 'credits' => ['/credits', 'Credits Wallet'], 'profile' => ['/profile', 'Profile']]
                        : ($navigation ?? []);
                @endphp
                @foreach($links as $key => [$path, $label])
                    @unless(str_contains($path, '{'))
                        <a @if(request()->routeIs("$area.$key")) aria-current="page" @endif href="{{ route("$area.$key") }}">{{ $label }}</a>
                    @endunless
                @endforeach
            </nav>
        @endif
        @yield('content')
    </main>
    <footer class="site-footer">
        <div class="wrap bar">
            <a class="brand" href="{{ route('home') }}"><img src="{{ asset('assets/YallaTeer-Logo-02.png') }}" width="112" height="72" alt="{{ __('YallaTeer home') }}"></a>
            @section('footer-links')
            <nav aria-label="{{ __('Footer') }}">
                @foreach(['about' => 'About', 'faq' => 'FAQ', 'terms' => 'Terms', 'privacy' => 'Privacy', 'contact' => 'Contact', 'assistant' => 'AI assistant'] as $name => $label)
                    <a href="{{ route($name) }}">{{ $label }}</a>
                @endforeach
            </nav>
            @show
        </div>
    </footer>
</body>
</html>

