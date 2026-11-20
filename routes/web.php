<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\PublicFlightController;
use App\Http\Middleware\SetLandingLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(SetLandingLocale::class)->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::view('/destinations', 'pages.destinations')->name('destinations');
});

Route::post('/language/{locale}', function (Request $request, string $locale) {
    $request->session()->put('landing_locale', $locale);

    return redirect()->route('home');
})->whereIn('locale', ['en', 'ar'])->name('home.language');

Route::redirect('/destinations/{country}', '/destinations');

Route::group(['where' => ['flight' => '[0-9]+']], function () {
    Route::resource('flights', PublicFlightController::class)
        ->only(['index', 'show'])
        ->names(['index' => 'flights.search']);
});

Route::view('/compare', 'flights.compare', ['title' => 'Compare'])
    ->name('compare');

Route::view('/booking-lookup', 'booking.lookup', ['title' => 'Booking Lookup'])
    ->name('booking.lookup');

Route::view('/assistant', 'pages.assistant', ['title' => 'AI Assistant'])
    ->name('assistant');

Route::view('/login', 'auth.login', ['title' => 'Login'])->name('login');
Route::view('/register', 'auth.register', ['title' => 'Register'])->name('register');

$informationPages = [
    'about' => 'About Us',
    'faq' => 'FAQ',
    'terms' => 'Terms and Conditions',
    'privacy' => 'Privacy Policy',
    'contact' => 'Contact and Suggestions',
];

foreach ($informationPages as $page => $title) {
    Route::view("/{$page}", 'pages.information', compact('page', 'title'))
        ->name($page);
}

$steps = [
    'passengers' => 'Passengers',
    'seats' => 'Seat Selection',
    'review' => 'Review',
    'payment' => 'Payment',
    'confirmation' => 'Confirmation',
];

foreach ($steps as $step => $title) {
    Route::view("/book/{$step}", 'booking.checkout', compact('step', 'steps', 'title'))
        ->name("book.{$step}");
}

Route::prefix('my')->name('my.')->group(function () {
    Route::view('/bookings', 'customer.account', [
        'screen' => 'bookings',
        'title' => 'My Bookings',
    ])->name('bookings');

    Route::view('/bookings/{code}', 'customer.account', [
        'screen' => 'booking',
        'title' => 'Booking Details',
    ])->name('booking');

    Route::view('/bookings/{code}/review', 'customer.account', [
        'screen' => 'review',
        'title' => 'Write a Review',
    ])->name('review');

    Route::view('/credits', 'customer.account', [
        'screen' => 'credits',
        'title' => 'Credits Wallet',
    ])->name('credits');

    Route::view('/profile', 'customer.account', [
        'screen' => 'profile',
        'title' => 'Profile',
    ])->name('profile');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])
    ->name('admin.login');

Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->name('admin.login.submit');

Route::view('/airline/login', 'airline-admin.login', [
    'title' => 'Airline Admin Login',
])->name('airline.login');

$adminPages = [
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
];

$airlinePages = [
    'dashboard' => ['', 'Dashboard'],
    'flights' => ['/flights', 'Flights List', ['Flight number', 'Route', 'Departure', 'Status']],
    'create' => ['/flights/create', 'Create Flight'],
    'edit' => ['/flights/{id}/edit', 'Edit Flight'],
    'flight' => ['/flights/{id}', 'Flight Details'],
    'bookings' => ['/bookings', 'Bookings', ['Code', 'Route', 'Status']],
    'booking' => ['/bookings/{id}', 'Booking Details'],
    'reviews' => ['/reviews', 'Reviews', ['Flight', 'Rating', 'Comment', 'Date']],
];

$registerPanelRoutes = function (string $area, string $view, array $navigation) {
    foreach ($navigation as $screen => $page) {
        [$path, $title] = $page;

        Route::view("/{$area}{$path}", $view, [
            'area' => $area,
            'screen' => $screen,
            'title' => $title,
            'columns' => $page[2] ?? [],
            'navigation' => $navigation,
        ])->whereNumber('id')->name("{$area}.{$screen}");
    }
};

$registerPanelRoutes('admin', 'super-admin.panel', $adminPages);
$registerPanelRoutes('airline', 'airline-admin.panel', $airlinePages);



Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/countries', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'index'])->name('countries');
    Route::get('/countries/create', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'create'])->name('countries.create');
    Route::post('/countries', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'store'])->name('countries.store');
    Route::get('/countries/{country}/edit', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'edit'])->whereNumber('country')->name('countries.edit');
    Route::put('/countries/{country}', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'update'])->whereNumber('country')->name('countries.update');
    Route::delete('/countries/{country}', [\App\Http\Controllers\SuperAdmin\CountryController::class, 'destroy'])->whereNumber('country')->name('countries.destroy');
});

Route::get('/flights', [\App\Http\Controllers\PublicSite\FlightController::class, 'index'])->name('flights.search');
Route::post('/flights/selection', [\App\Http\Controllers\PublicSite\FlightController::class, 'select'])->name('flights.selection');
Route::get('/flights/{flight}', [\App\Http\Controllers\PublicSite\FlightController::class, 'show'])->whereNumber('flight')->name('flights.show');

Route::get('/compare', [\App\Http\Controllers\PublicSite\CompareController::class, 'index'])->name('compare');

Route::get('/admin/credits', [\App\Http\Controllers\SuperAdmin\CreditController::class, 'index'])->middleware('auth')->name('admin.credits');
Route::post('/admin/credits/{customer}', [\App\Http\Controllers\SuperAdmin\CreditController::class, 'update'])->whereNumber('customer')->middleware('auth')->name('admin.credits.update');

Route::get('/my/booking-flights/{bookingFlight}/review', [\App\Http\Controllers\Customer\ReviewController::class, 'create'])->whereNumber('bookingFlight')->middleware('auth')->name('my.reviews.create');
Route::post('/my/booking-flights/{bookingFlight}/review', [\App\Http\Controllers\Customer\ReviewController::class, 'store'])->whereNumber('bookingFlight')->middleware('auth')->name('my.reviews.store');

Route::get('/assistant', [\App\Http\Controllers\Customer\AiConversationController::class, 'index'])->middleware('auth')->name('assistant');
Route::get('/my/assistant', [\App\Http\Controllers\Customer\AiConversationController::class, 'index'])->middleware('auth')->name('my.assistant');
Route::post('/my/assistant', [\App\Http\Controllers\Customer\AiConversationController::class, 'store'])->middleware(['auth', 'throttle:10,1'])->name('my.assistant.store');
Route::get('/my/assistant/{conversation}', [\App\Http\Controllers\Customer\AiConversationController::class, 'show'])->whereNumber('conversation')->middleware('auth')->name('my.assistant.show');

Route::get('/admin', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->middleware('auth')->name('admin.dashboard');

Route::get('/faq', [\App\Http\Controllers\PublicSite\InformationController::class, 'faq'])->name('faq');
Route::get('/terms', [\App\Http\Controllers\PublicSite\InformationController::class, 'terms'])->name('terms');



