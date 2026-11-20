<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AirlineAdminSessionController
{
    public function create(): View
    {
        return view('airline-admin.login');
    }

    public function store(LoginRequest $request, AuthService $authService): RedirectResponse
    {
        if (!$authService->attempt($request->validated(), AuthService::ROLE_AIRLINE_ADMIN, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        return redirect()->intended(route('airline.dashboard'));
    }

    public function destroy(AuthService $authService): RedirectResponse
    {
        $authService->logout();

        return redirect()->route('airline.login');
    }
}
