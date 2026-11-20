<?php

namespace App\Providers;

use App\Services\GeminiPassportExtractor;
use App\Services\PassportExtraction;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PassportExtraction::class, GeminiPassportExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = Str::transliterate(Str::lower((string) $request->input('email', '')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
