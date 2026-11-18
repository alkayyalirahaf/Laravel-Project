<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'travel.home', ['title' => 'Find your flight'])->name('home');
Route::view('/flights', 'travel.results', ['title' => 'Search Results'])->name('flights.search');
Route::view('/flights/{flight}', 'travel.flight', ['title' => 'Flight Details'])->whereNumber('flight')->name('flights.show');
Route::view('/compare', 'travel.compare', ['title' => 'Compare'])->name('compare');
Route::view('/destinations/{country?}', 'travel.destinations', ['title' => 'Destination Guide'])->name('destinations');
Route::view('/booking-lookup', 'travel.lookup', ['title' => 'Booking Lookup'])->name('booking.lookup');
Route::view('/assistant', 'travel.assistant', ['title' => 'AI Assistant'])->name('assistant');

Route::view('/login', 'travel.login', ['title' => 'Login'])->name('login');
Route::view('/register', 'travel.register', ['title' => 'Register'])->name('register');

foreach (['about' => 'About Us', 'faq' => 'FAQ', 'terms' => 'Terms and Conditions', 'privacy' => 'Privacy Policy', 'contact' => 'Contact and Suggestions'] as $page => $title) {
    Route::view("/$page", 'travel.public', compact('page', 'title'))->name($page);
}

$steps = ['passengers' => 'Passengers', 'seats' => 'Seat Selection', 'review' => 'Review', 'payment' => 'Payment', 'confirmation' => 'Confirmation'];
foreach ($steps as $step => $title) {
    Route::view("/book/$step", 'travel.checkout', compact('step', 'steps', 'title'))->name("book.$step");
}

Route::prefix('my')->name('my.')->group(function () {
    foreach ([
        'bookings' => ['/bookings', 'My Bookings'],
        'booking' => ['/bookings/{code}', 'Booking Details'],
        'review' => ['/bookings/{code}/review', 'Write a Review'],
        'credits' => ['/credits', 'Credits Wallet'],
        'profile' => ['/profile', 'Profile'],
    ] as $screen => [$path, $title]) {
        Route::view($path, 'travel.account', compact('screen', 'title'))->name($screen);
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
    $staffTitle = $area === 'admin' ? 'Admin Login' : 'Airline Admin Login';
    Route::view("/$area/login", 'travel.login', ['title' => $staffTitle])->name("$area.login");
    foreach ($navigation as $screen => $definition) {
        [$path, $title] = $definition;
        $columns = $definition[2] ?? [];
        Route::view("/$area".$path, 'travel.staff', compact('area', 'screen', 'title', 'columns', 'navigation'))
            ->whereNumber('id')->name("$area.$screen");
    }
}
