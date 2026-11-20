<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisteredUserController
{
    public function create()
    {
        return view('auth.register', ['title' => 'Register']);
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->customer()->create([
                'phone' => $data['phone'] ?? null,
                'credit_balance' => '0.00',
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('my.bookings'));
    }
}
