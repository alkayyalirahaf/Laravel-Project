<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;

Route::get('/', function () {
    return view('welcome');
});




//admin 
Route::get('admin/login', [AdminAuthController::class, 'showLogin'])->name('login');
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('login.submit');
Route::get('/', function () {
    return view('admin/index');})->name('admin.index');
