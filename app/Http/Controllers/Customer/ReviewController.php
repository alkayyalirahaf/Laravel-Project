<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Models\BookingFlight;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewController
{
    public function create(Request $request, int $bookingFlight)
    {
        $segment = $this->ownedSegment($request, $bookingFlight);
        $this->checkEligibility($segment);

        return view('omar.reviews.create', compact('segment'));
    }

    public function store(Request $request, int $bookingFlight)
    {
        // Check ownership before validation so another customer's segment is never exposed.
        $segment = $this->ownedSegment($request, $bookingFlight);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $customerId = $request->user()->customer->id;
        DB::transaction(function () use ($segment, $data, $customerId) {
            // Lock the booking and segment before checking again. Concurrent submissions
            // through this controller serialize on the same parent records.
            $booking = $segment->booking()->lockForUpdate()->firstOrFail();
            abort_unless((int) $booking->customer_id === (int) $customerId, 403);
            $segment = BookingFlight::whereKey($segment->id)->lockForUpdate()->firstOrFail();
            $segment->setRelation('booking', $booking);
            $segment->load('flight');
            $this->checkEligibility($segment);

            Review::create([
                'booking_flight_id' => $segment->id,
                'customer_id' => $booking->customer_id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
                // Dev 1 owns moderation; new reviews await their decision.
                'status' => 'hidden',
            ]);
        });

        return redirect()->route('my.bookings')->with('status', 'Review submitted for moderation.');
    }

    private function ownedSegment(Request $request, int $id): BookingFlight
    {
        $customer = $request->user()?->customer;
        abort_unless($customer, 403);

        return BookingFlight::with('booking', 'flight.airline')
            ->whereHas('booking', fn ($query) => $query->where('customer_id', $customer->id))
            ->findOrFail($id);
    }

    private function checkEligibility(BookingFlight $segment): void
    {
        if ($segment->booking->status !== 'completed' || $segment->flight->status !== 'completed'
            || !$segment->flight->arrival_at->isPast()) {
            throw ValidationException::withMessages(['review' => 'Reviews are available only after your booked flight is completed.']);
        }

        if ($segment->reviews()->where('customer_id', $segment->booking->customer_id)->exists()) {
            throw ValidationException::withMessages(['review' => 'You have already reviewed this flight segment.']);
        }
    }
}
