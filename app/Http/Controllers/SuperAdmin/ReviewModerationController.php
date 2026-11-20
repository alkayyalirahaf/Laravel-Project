<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;



class ReviewModerationController
{
    public function index()
    {
        $reviews = Review::with(['customer.user', 'bookingFlight.flight.airline'])
            ->latest()
            ->paginate(15);

        return view('super-admin.reviews.index', compact('reviews'));
    }

    public function update(Request $request, Review $review)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['visible', 'hidden'])],
        ]);

        $review->update(['status' => $data['status']]);

        return back()->with('success', 'Review status updated.');
    }
}
