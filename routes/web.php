<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AirlineLoginController;

Route::prefix('airline')->name('airline.')->group(function () {
    Route::get('/login', [AirlineLoginController::class, 'create'])->name('login');
    Route::post('/login', [AirlineLoginController::class, 'store'])->name('login.store');
    
    Route::get('/verify-otp', [AirlineLoginController::class, 'showOtpForm'])->name('otp.form');
    Route::post('/verify-otp', [AirlineLoginController::class, 'storeotp'])->name('otp.verify');
    
    Route::post('/logout', [AirlineLoginController::class, 'destroy'])->middleware('auth')->name('logout');
});
