<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\Auth\AirlineAdminSessionController;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Customer\CreditWalletController;
use App\Http\Controllers\PublicSite\ContactMessageController;
use App\Http\Controllers\PublicSite\HomeController;
use App\Http\Controllers\SuperAdmin\AirportController;
use App\Http\Middleware\SetLandingLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->middleware(SetLandingLocale::class)->name('home');

Route::post('/language/{locale}', function (Request $request, string $locale) {
    $request->session()->put('landing_locale', $locale);

    return redirect()->route('home');
})->whereIn('locale', ['en', 'ar'])->name('home.language');
Route::view('/flights', 'flights.index', ['title' => 'Search Results'])->name('flights.search');
Route::view('/flights/{flight}', 'flights.show', ['title' => 'Flight Details'])->whereNumber('flight')->name('flights.show');
Route::view('/compare', 'flights.compare', ['title' => 'Compare'])->name('compare');
Route::view('/destinations', 'pages.destinations')->middleware(SetLandingLocale::class)->name('destinations');
Route::redirect('/destinations/{country}', '/destinations');
Route::view('/booking-lookup', 'booking.lookup', ['title' => 'Booking Lookup'])->name('booking.lookup');
Route::view('/assistant', 'pages.assistant', ['title' => 'AI Assistant'])->name('assistant');

Route::get('/login', [CustomerSessionController::class, 'create'])->name('login');
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/login', [CustomerSessionController::class, 'store'])->middleware('throttle:auth-login')->name('login.store');
Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
Route::post('/logout', [CustomerSessionController::class, 'destroy'])->middleware('auth')->name('logout');

foreach (['about' => 'About Us', 'faq' => 'FAQ', 'terms' => 'Terms and Conditions', 'privacy' => 'Privacy Policy', 'contact' => 'Contact and Suggestions'] as $page => $title) {
    Route::view("/$page", 'pages.information', compact('page', 'title'))->name($page);
}
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

$steps = ['passengers' => 'Passengers', 'seats' => 'Seat Selection', 'review' => 'Review', 'payment' => 'Payment', 'confirmation' => 'Confirmation'];
foreach ($steps as $step => $title) {
    Route::view("/book/$step", 'booking.checkout', compact('step', 'steps', 'title'))
        ->middleware('role.customer')
        ->name("book.$step");
}

Route::prefix('my')->name('my.')->middleware('role.customer')->group(function () {
    foreach ([
        'bookings' => ['/bookings', 'My Bookings'],
        'booking' => ['/bookings/{code}', 'Booking Details'],
        'review' => ['/bookings/{code}/review', 'Write a Review'],
        'credits' => ['/credits', 'Credits Wallet'],
        'profile' => ['/profile', 'Profile'],
    ] as $screen => [$path, $title]) {
        if ($screen === 'credits') {
            Route::get($path, [CreditWalletController::class, 'index'])->name($screen);
        } else {
            Route::view($path, 'customer.account', compact('screen', 'title'))->name($screen);
        }
    }
});

$staff = [
    'admin' => [
        'dashboard' => ['', 'Dashboard'],
        'airlines' => ['/airlines', 'Airlines', ['Name', 'Airline code', 'Country', 'Status']],
        'airline-admins' => ['/airline-admins', 'Airline Admins', ['Name', 'Email', 'Airline', 'Job title']],
        'customers' => ['/customers', 'Customers', ['Name', 'Email', 'Phone', 'Balance', 'Bookings']],
        'countries' => ['/countries', 'Countries', ['Name', 'Country code', 'Currency', 'Language', 'Local apps']],
        'cities' => ['/cities', 'Cities', ['Name', 'Country', 'Transport', 'Tips', 'Best season']],
        'airports' => ['/airports', 'Airports', ['Name', 'IATA code', 'City']],
        'flights' => ['/flights', 'Flights', ['Flight number', 'Airline', 'Route', 'Departure', 'Status']],
        'bookings' => ['/bookings', 'Bookings', ['Code', 'Route', 'Status', 'Total']],
        'booking' => ['/bookings/{id}', 'Booking Details'],
        'payments' => ['/payments', 'Payments', ['Booking', 'Receipt', 'Amount', 'Status', 'Stripe ID', 'Date']],
        'credits' => ['/credits', 'Credits', ['Customer', 'Amount', 'Type', 'Date']],
        'reviews' => ['/reviews', 'Review Moderation', ['Customer', 'Flight', 'Rating', 'Comment', 'Status']],
        'ai-conversations' => ['/ai-conversations', 'AI Conversations', ['Customer', 'Subject', 'Date']],
        'notifications' => ['/notifications', 'Notification Logs', ['Channel', 'Type', 'Scheduled', 'Sent', 'Status']],
        'messages' => ['/messages', 'Messages', ['Type', 'Sender', 'Email', 'Message', 'Status']],
    ],
    'airline' => [
        'dashboard' => ['', 'Dashboard'],
        'flights' => ['/flights', 'Flights List', ['Flight number', 'Route', 'Departure', 'Status']],
        'create' => ['/flights/create', 'Create Flight'],
        'edit' => ['/flights/{id}/edit', 'Edit Flight'],
        'flight' => ['/flights/{id}', 'Flight Details'],
        'bookings' => ['/bookings', 'Bookings', ['Code', 'Route', 'Status']],
        'booking' => ['/bookings/{id}', 'Booking Details'],
        'reviews' => ['/reviews', 'Reviews', ['Flight', 'Rating', 'Comment', 'Date']],
    ],
];

foreach ($staff as $area => $navigation) {
    $folder = $area === 'admin' ? 'super-admin' : 'airline-admin';
    $roleMiddleware = $area === 'admin' ? 'role.super-admin' : 'role.airline-admin';

    Route::prefix($area)->name("$area.")->group(function () use ($area, $folder, $navigation, $roleMiddleware) {
        if ($area === 'admin') {
            Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
        } else {
            Route::get('/login', [AirlineAdminSessionController::class, 'create'])->name('login');
            Route::post('/login', [AirlineAdminSessionController::class, 'store'])->middleware('throttle:auth-login')->name('login.submit');
        }

        Route::middleware($roleMiddleware)->group(function () use ($area, $folder, $navigation) {
            foreach ($navigation as $screen => $definition) {
                [$path, $title] = $definition;
                $columns = $definition[2] ?? [];

                if ($area === 'admin' && $screen === 'airports') {
                    Route::get($path, [AirportController::class, 'index'])->name('airports');
                    Route::get($path.'/create', [AirportController::class, 'create'])->name('airports.create');
                    Route::post($path, [AirportController::class, 'store'])->name('airports.store');
                    Route::get($path.'/{airport}/edit', [AirportController::class, 'edit'])->name('airports.edit');
                    Route::put($path.'/{airport}', [AirportController::class, 'update'])->name('airports.update');
                    Route::delete($path.'/{airport}', [AirportController::class, 'destroy'])->name('airports.destroy');

                    continue;
                }

                Route::view($path, "$folder.panel", compact('area', 'screen', 'title', 'columns', 'navigation'))->whereNumber('id')->name($screen);
            }
        });
    });
}
