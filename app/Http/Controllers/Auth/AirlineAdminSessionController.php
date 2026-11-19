<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;

class AirlineAdminSessionController
{
    public function create()
    {
        return view('airline-admin.login', [
            'title' => 'Airline Admin Login',
            'action' => route('airline.login.submit'),
        ]);
    }

    public function store(LoginRequest $request, AuthService $authService)
    {
        if (! $authService->attempt($request->validated(), AuthService::ROLE_AIRLINE_ADMIN)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        return redirect()->intended(route('airline.dashboard'));
    }
}
