<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AirlineAdminSessionController
{
    public function create(): View
    {
return view('airline-admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is wrong, please try again.'])
                ->onlyInput('email');
        }

        $user = Auth::user();
        $airlineAdmin = $user->airlineAdmin;

        if (! $airlineAdmin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'You do not have permission to enter this page.'])
                ->onlyInput('email');
        }

        if ($airlineAdmin->airline?->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Your airline account is not active. Please contact support.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('airline.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('airline.login');
    }
}
