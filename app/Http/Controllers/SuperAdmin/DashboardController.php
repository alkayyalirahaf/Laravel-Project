<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Http\Request;
use App\Models\Airline;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Flight;
use App\Models\Payment;

class DashboardController
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->superAdmin, 403);

        $statistics = [
            'Customers' => Customer::count(),
            'Airlines' => Airline::count(),
            'Flights' => Flight::count(),
            'Bookings' => Booking::count(),
            'Payments' => Payment::count(),
            'Successful payments' => Payment::where('status', 'succeeded')->count(),
        ];
        $recentBookings = Booking::with('customer.user')->latest('id')->limit(10)->get();
        $recentPayments = Payment::with('booking')->latest('id')->limit(10)->get();

        return view('super-admin.panel', array_merge(compact('statistics', 'recentBookings', 'recentPayments'), ['area' => 'admin', 'screen' => 'dashboard', 'title' => 'Super Admin Dashboard']));
    }
}
