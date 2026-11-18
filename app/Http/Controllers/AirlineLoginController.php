<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AirlineLoginController extends Controller
{
    public function create()
    {
        return view('airline.auth.login');
    }

    public function store(Request $request)
    {
        $datauser = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::validate($datauser)) {
            return back()->withErrors([
                'email' => 'The email or password is wrong, please try again.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $datauser['email'])->first();

        if (!$user->airlineAdmin()->exists()) {
            return back()->withErrors([
                'email' => 'You do not have permission to enter this page.',
            ])->onlyInput('email');
        }

        $otp = random_int(100000, 999999);

        session([
            'airline_otp_user_id'    => $user->id,
            'airline_otp_code'       => $otp,
            'airline_otp_expires_at' => now()->addMinutes(5),
        ]);

        Mail::raw("This is the code for confirmation: {$otp} (valid for 5 minutes).", function ($message) use ($user) {
            $message->to($user->email)->subject('Code for login - Yalla Teer');
        });

        return redirect()->route('airline.otp.form')->with('status', 'We sent the OTP to your email.');
    }

    public function showOtpForm()
    {
        if (!session()->has('airline_otp_user_id')) {
            return redirect()->route('airline.login');
        }

        return view('airline.auth.verify-otp');
    }

    public function storeotp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $userId    = session('airline_otp_user_id');
        $savedOtp  = session('airline_otp_code');
        $expiresAt = session('airline_otp_expires_at');

        if (!$userId || !$savedOtp || now()->greaterThan($expiresAt)) {
            session()->forget(['airline_otp_user_id', 'airline_otp_code', 'airline_otp_expires_at']);

            return redirect()->route('airline.login')->withErrors([
                'email' => 'The verification code has expired Please log in again.',
            ]);
        }

        if (!hash_equals((string) $savedOtp, (string) $request->otp)) {
    return back()->withErrors([
        'otp' => 'The verification code you entered is invalid.',
    ]);
}

        session()->forget(['airline_otp_user_id', 'airline_otp_code', 'airline_otp_expires_at']);
        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return redirect()->intended(route('airline.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('airline.login');
    }
}
