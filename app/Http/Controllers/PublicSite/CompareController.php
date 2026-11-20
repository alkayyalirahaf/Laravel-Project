<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\Http\Request;
use App\Services\FlightSearchService;
use Illuminate\Validation\ValidationException;

class CompareController
{
    public function index(Request $request, FlightSearchService $search)
    {
        $flights = collect();
        if ($request->has('flights')) {
            $data = $request->validate([
                'flights' => ['required', 'array', 'min:2', 'max:3'],
                'flights.*' => ['required', 'integer', 'distinct', 'min:1'],
            ]);

            $flights = $search->availableFlights()->whereIn('id', $data['flights'])
                ->with(['airline', 'originAirport', 'destinationAirport', 'flightClasses' => fn ($query) => $query->withCount([
                    'seats as available_seats_count' => fn ($seats) => $seats->where('status', 'available'),
                ])->orderBy('price')])->get();

            if ($flights->count() !== count($data['flights'])) {
                throw ValidationException::withMessages(['flights' => 'Every selected flight must exist and be available from an active airline.']);
            }
        }

        return view('omar.flights.compare', compact('flights'));
    }
}
