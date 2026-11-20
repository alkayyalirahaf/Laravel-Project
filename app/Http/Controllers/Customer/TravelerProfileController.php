<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TravelerProfileController
{
    public function show(Request $request)
    {
        return view('booking.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30']]);
        DB::transaction(function () use ($request, $data): void {
            $request->user()->update(['name' => $data['name']]);
            $request->user()->customer->update(['phone' => $data['phone'] ?? null]);
        });

        return redirect()->route('my.profile')->with('status', 'Profile updated.');
    }
}
