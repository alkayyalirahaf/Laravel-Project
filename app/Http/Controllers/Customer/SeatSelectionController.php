<?php

namespace App\Http\Controllers\Customer;

use App\Models\Booking;
use App\Models\FlightClass;
use App\Models\Seat;
use App\Services\BookingFinalizationService;
use App\Services\FlightSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeatSelectionController
{
    public function select(Request $request, FlightSearchService $search)
    {
        $selection = $request->session()->get('dev3.search_selection');
        $draft = $request->session()->get('dev4.passenger_draft');
        if (!$selection || empty($draft['booking_code']) || ($draft['selection_id'] ?? null) !== hash('sha256', serialize($selection))) {
            return redirect()->route('book.passengers');
        }

        $customer = $request->user()->customer;
        $details = Crypt::decrypt($draft['details']);
        $code = $draft['booking_code'];
        $existing = $customer->bookings()->where('code', $code)->first();
        if ($existing) {
            return redirect()->route('book.review', ['booking_id' => $existing->id]);
        }

        $legs = array_filter(['outbound' => $selection['outbound'], 'return' => $selection['return'] ?? null]);
        $cabins = [];
        foreach ($legs as $direction => $leg) {
            $cabins[$direction] = FlightClass::whereKey($leg['flight_class_id'])
                ->where('flight_id', $leg['flight_id'])
                ->whereIn('flight_id', $search->availableFlights()->select('id'))
                ->with(['flight', 'seats' => fn ($query) => $query->orderBy('id')])
                ->first();

            if (!$cabins[$direction]) {
                return redirect()->route('flights.search')->withErrors(['selection' => 'This itinerary is no longer available.']);
            }
        }

        $passengers = $details['passengers'];
        if ($request->isMethod('get')) {
            $maps = [];
            foreach ($cabins as $direction => $cabin) {
                $rows = [];
                $ids = [];
                foreach ($cabin->seats as $seat) {
                    if (!preg_match('/^(\d+)([A-Z]+)$/D', $seat->seat_number, $parts)) {
                        continue;
                    }

                    [, $row, $column] = $parts;
                    $rows[$row][$column] = $seat->status === 'available'
                        || ($seat->status === 'held' && $seat->held_until?->lte(now()));
                    $ids[$seat->seat_number] = $seat->id;
                }

                ksort($rows, SORT_NUMERIC);
                $maps[$direction] = compact('rows', 'ids');
            }

            return view('booking.select-seats', compact('cabins', 'passengers', 'code', 'maps'));
        }

        $rules = ['booking_code' => ['required', 'in:'.$code], 'seats' => ['required', 'array:'.implode(',', array_keys($legs))]];
        foreach ($legs as $direction => $leg) {
            $rules['seats.'.$direction] = ['required', 'array', 'list', 'size:'.count($passengers)];
            $rules['seats.'.$direction.'.*'] = ['required', 'integer', 'min:1'];
        }
        $validated = $request->validate($rules);

        $booking = DB::transaction(function () use ($customer, $code, $selection, $legs, $details, $validated, $search): Booking {
            $customer->newQuery()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $existing = $customer->bookings()->where('code', $code)->first();
            if ($existing) {
                return $existing;
            }

            $choices = $validated['seats'];
            $ids = collect($choices)->flatten();
            $seats = Seat::whereKey($ids->all())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($seats->count() !== $ids->count()) {
                throw ValidationException::withMessages(['seats' => 'Choose one distinct seat per traveler on each flight.']);
            }

            $subtotal = 0;
            foreach ($legs as $direction => $leg) {
                $cabin = FlightClass::whereKey($leg['flight_class_id'])->where('flight_id', $leg['flight_id'])
                    ->whereIn('flight_id', $search->availableFlights()->select('id'))->first();
                if (!$cabin || $cabin->price !== (string) $leg['price']) {
                    throw ValidationException::withMessages(['seats' => 'The flight or fare changed. Please search again.']);
                }

                $subtotal += (int) str_replace('.', '', $cabin->price) * count($details['passengers']);

                foreach ($choices[$direction] as $seatId) {
                    $seat = $seats->get($seatId);
                    if ((int) $seat->flight_class_id !== (int) $cabin->id
                        || !($seat->status === 'available' || ($seat->status === 'held' && $seat->held_until?->isPast()))) {
                        throw ValidationException::withMessages(['seats' => 'A selected seat is no longer available. Choose another seat.']);
                    }
                }
            }

            $total = sprintf('%d.%02d', intdiv($subtotal, 100), $subtotal % 100);
            $booking = $customer->bookings()->create([
                'code' => $code, 'trip_type' => $selection['trip'], 'status' => 'pending',
                'subtotal' => $total, 'taxes' => 0, 'fees' => 0, 'total' => $total,
                'credits_used' => 0, 'amount_paid' => 0, 'contact_email' => $details['contact_email'],
                'contact_phone' => $details['contact_phone'] ?? null,
            ]);

            foreach ($details['passengers'] as $passenger) {
                if ($passenger['type'] === 'youth') {
                    $passenger['type'] = 'adult';
                }
                $booking->passengers()->create($passenger + ['filled_by_ai' => false]);
            }

            foreach ($legs as $direction => $leg) {
                $booking->bookingFlights()->create([
                    'flight_id' => $leg['flight_id'], 'flight_class_id' => $leg['flight_class_id'], 'direction' => $direction,
                ]);
            }

            $expires = now()->addMinutes(15)->startOfSecond();
            foreach ($seats as $seat) {
                $seat->update(['status' => 'held', 'held_by' => $customer->id, 'held_until' => $expires]);
            }
            $stored = Cache::store('database')->put(BookingFinalizationService::holdKey($booking), [
                'seats' => $choices, 'expires' => $expires->timestamp,
            ], $expires);
            abort_unless($stored, 503, 'The seat hold could not be saved. Please try again.');

            return $booking;
        }, 3);

        return redirect()->route('book.review', ['booking_id' => $booking->id]);
    }
}
