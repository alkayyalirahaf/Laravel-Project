<?php

namespace App\Http\Controllers\Customer;

use App\Models\FlightClass;
use App\Services\FlightSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class BookingFlowController
{
    public function passengers(Request $request, FlightSearchService $search)
    {
        $selection = $request->session()->get('dev3.search_selection');
        $counts = $selection['passenger_counts'] ?? null;

        if (! $counts || empty($selection['outbound'])) {
            return redirect()->route('flights.search')->withErrors([
                'selection' => 'Select a flight and passenger counts before continuing.',
            ]);
        }

        $legs = ['Outbound' => $selection['outbound']];
        if ($selection['trip'] === 'round_trip') {
            $legs['Return'] = $selection['return'];
        }

        $cabins = [];
        foreach ($legs as $label => $leg) {
            $cabin = FlightClass::query()
                ->whereKey($leg['flight_class_id'])
                ->where('flight_id', $leg['flight_id'])
                ->whereIn('flight_id', $search->availableFlights()->select('id'))
                ->whereHas('seats', fn ($query) => $query->availableForBooking(), '>=', $selection['passengers'])
                ->with(['flight.originAirport', 'flight.destinationAirport'])
                ->first();

            if (! $cabin) {
                return redirect()->route('flights.search')->withErrors([
                    'selection' => 'A selected flight or cabin is no longer available. Please search again.',
                ]);
            }

            $cabins[$label] = $cabin;
        }

        $travelerTypes = array_merge(
            array_fill(0, $counts['adults'], 'adult'),
            array_fill(0, $counts['youth'], 'youth'),
            array_fill(0, $counts['children'], 'child'),
        );
        $selectionId = hash('sha256', serialize($selection));
        $draftKey = 'dev4.passenger_draft';
        $draft = $request->session()->get($draftKey);

        if ($request->isMethod('get')) {
            $draft = ($draft['selection_id'] ?? null) === $selectionId ? Crypt::decrypt($draft['details']) : [];

            return view('booking.passenger-selection', compact('travelerTypes', 'cabins', 'draft', 'selectionId'));
        }

        if ($request->input('selection_id') !== $selectionId) {
            return redirect()->route('book.passengers')->withErrors([
                'selection' => 'Your flight selection changed. Review the current itinerary and try again.',
            ]);
        }

        $lastDeparture = collect($cabins)->max(fn ($cabin) => $cabin->flight->departure_at->toDateString());
        $input = $request->all();
        if (is_array($input['passengers'] ?? null)) {
            foreach ($input['passengers'] as $index => $passenger) {
                if (is_array($passenger) && array_key_exists('filled_by_ai', $passenger)) {
                    $input['passengers'][$index]['filled_by_ai'] = in_array($passenger['filled_by_ai'], [true, 1, '1'], true);
                }
            }
        }

        $validator = Validator::make($input, [
            'passengers' => ['required', 'array', 'list', 'size:'.count($travelerTypes)],
            'passengers.*' => ['required', 'array:first_name,last_name,date_of_birth,gender,nationality,passport_number,passport_country,passport_expiry,special_assistance,filled_by_ai'],
            'passengers.*.first_name' => ['required', 'string', 'max:100'],
            'passengers.*.last_name' => ['required', 'string', 'max:100'],
            'passengers.*.date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'passengers.*.gender' => ['required', 'in:male,female'],
            'passengers.*.nationality' => ['required', 'string', 'max:60'],
            'passengers.*.passport_number' => ['required', 'string', 'max:20'],
            'passengers.*.passport_country' => ['required', 'string', 'max:60'],
            'passengers.*.passport_expiry' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$lastDeparture],
            'passengers.*.special_assistance' => ['nullable', 'string', 'max:255'],
            'passengers.*.filled_by_ai' => ['nullable', 'boolean'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('book.passengers')->withErrors($validator);
        }

        $details = $validator->validated();
        foreach ($details['passengers'] as $index => &$passenger) {
            $passenger['filled_by_ai'] = $passenger['filled_by_ai'] ?? false;
            $passenger['type'] = $travelerTypes[$index];
        }
        unset($passenger);

        $request->session()->put($draftKey, ['selection_id' => $selectionId, 'details' => Crypt::encrypt($details), 'booking_code' => 'YTB'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 9))]);

        return redirect()->route('book.seats');
    }
}
