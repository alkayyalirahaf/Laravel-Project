<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;

class BookingLookupController
{
    public function search(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:12']]);
        $booking = $request->user()->customer->bookings()->where('code', strtoupper($data['code']))->first();

        if (!$booking) {
            return back()->withErrors(['code' => 'No matching booking was found.'])->withInput();
        }

        return redirect()->route('my.booking', $booking->code);
    }
}
