<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;

class CustomerSessionController
{
    public function create()
    {
        return view('auth.login', [
            'title' => 'Login',
            'action' => route('login'),
        ]);
    }

    public function store(LoginRequest $request, AuthService $authService)
    {
        if (! $authService->attempt($request->validated(), AuthService::ROLE_CUSTOMER)) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        return redirect()->intended(route('my.bookings'));
    }

    public function destroy(AuthService $authService)
    {
        $authService->logout();

        return redirect()->route('home');
    }
}
