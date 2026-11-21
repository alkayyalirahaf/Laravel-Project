@auth
    <details class="nav-account">
        <summary class="nav-account-trigger" aria-label="{{ __('My account') }}">
            <span class="nav-avatar nav-avatar--guest" aria-hidden="true"></span>
        </summary>
        <div class="nav-account-panel">
            @if(auth()->user()->customer)
                <a href="{{ route('my.profile') }}">{{ __('Show my profile') }}</a>
                <a href="{{ route('my.bookings') }}">{{ __('My bookings') }}</a>
            @endif
            @if(\Illuminate\Support\Facades\Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">{{ __('Sign out') }}</button>
                </form>
            @endif
        </div>
    </details>
@else
    <a class="nav-account-trigger" href="{{ route('login') }}">
        <span class="nav-avatar nav-avatar--guest" aria-hidden="true"></span>
        {{ __('Sign in') }}
    </a>
@endauth
