<div class="landing-language">
    @foreach(['en' => 'English', 'ar' => 'العربية'] as $locale => $language)
        <form method="POST" action="{{ route('home.language', $locale) }}">
            @csrf
            <button type="submit" lang="{{ $locale }}" aria-label="{{ $language }}" aria-pressed="{{ app()->isLocale($locale) ? 'true' : 'false' }}">{{ strtoupper($locale) }}</button>
        </form>
    @endforeach
</div>
