<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Seat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FlightInventoryService
{
    public function createFlight(int $airlineId, array $flightData, array $classesData): Flight
    {
        $this->validateFlightRules($flightData, $classesData);

        return DB::transaction(function () use ($airlineId, $flightData, $classesData) {
            $flight = Flight::create([
                'airline_id'             => $airlineId,
                'flight_number'          => $flightData['flight_number'],
                'origin_airport_id'      => $flightData['origin_airport_id'],
                'destination_airport_id' => $flightData['destination_airport_id'],
                'departure_at'           => $flightData['departure_at'],
                'arrival_at'             => $flightData['arrival_at'],
                'stops'                  => $flightData['stops'] ?? 0,
                'status'                 => $flightData['status'] ?? 'scheduled',
            ]);

            $this->syncClassesAndSeats($flight, $classesData);

            return $flight->load(['flightClasses.seats', 'originAirport', 'destinationAirport']);
        });
    }

    public function updateFlight(Flight $flight, array $flightData, array $classesData): Flight
    {
        $this->validateFlightRules($flightData, $classesData);

        return DB::transaction(function () use ($flight, $flightData, $classesData) {
            $flight->update([
                'flight_number'          => $flightData['flight_number'],
                'origin_airport_id'      => $flightData['origin_airport_id'],
                'destination_airport_id' => $flightData['destination_airport_id'],
                'departure_at'           => $flightData['departure_at'],
                'arrival_at'             => $flightData['arrival_at'],
                'stops'                  => $flightData['stops'] ?? 0,
                'status'                 => $flightData['status'] ?? $flight->status,
            ]);

            $this->syncClassesAndSeats($flight, $classesData);

            return $flight->load(['flightClasses.seats', 'originAirport', 'destinationAirport']);
        });
    }

    protected function validateFlightRules(array $flightData, array $classesData): void
    {
        if ((int) $flightData['origin_airport_id'] === (int) $flightData['destination_airport_id']) {
            throw ValidationException::withMessages([
                'destination_airport_id' => 'Destination airport must be different from origin airport.',
            ]);
        }

        if (strtotime($flightData['arrival_at']) <= strtotime($flightData['departure_at'])) {
            throw ValidationException::withMessages([
                'arrival_at' => 'Arrival time must be after departure time.',
            ]);
        }

        $classTypes = array_column($classesData, 'class');
        if (count($classTypes) !== count(array_unique($classTypes))) {
            throw ValidationException::withMessages([
                'classes' => 'Duplicate cabin classes are not allowed on the same flight.',
            ]);
        }
    }

    protected function syncClassesAndSeats(Flight $flight, array $classesData): void
    {
        $keptClassIds = [];

        foreach ($classesData as $classItem) {
            $rows = (int) $classItem['seat_rows'];
            $perRow = (int) $classItem['seats_per_row'];
            $totalSeats = $rows * $perRow;

            $flightClass = isset($classItem['id'])
                ? $flight->flightClasses()->where('id', $classItem['id'])->firstOrFail()
                : $flight->flightClasses()->firstOrNew(['class' => $classItem['class']]);

            if ($flightClass->exists) {
                $protectedCount = $flightClass->seats()->whereIn('status', ['booked', 'held'])->count();
                if ($totalSeats < $protectedCount) {
                    throw ValidationException::withMessages([
                        'classes' => "Cannot reduce seats in {$flightClass->class} below active/booked seats ({$protectedCount}).",
                    ]);
                }
            }

            $flightClass->fill([
                'class'              => $classItem['class'],
                'price'              => $classItem['price'],
                'seat_rows'          => $rows,
                'seats_per_row'      => $perRow,
                'total_seats'        => $totalSeats,
                'cabin_baggage_kg'   => $classItem['cabin_baggage_kg'] ?? 7,
                'checked_baggage_kg' => $classItem['checked_baggage_kg'] ?? 23,
            ]);
            $flightClass->save();

            $keptClassIds[] = $flightClass->id;

            $desiredSeatNumbers = [];
            $letters = range('A', 'Z');

            for ($r = 1; $r <= $rows; $r++) {
                for ($c = 0; $c < $perRow; $c++) {
                    $desiredSeatNumbers[] = $r . $letters[$c];
                }
            }

            $conflictingSeat = $flightClass->seats()
                ->whereIn('status', ['booked', 'held'])
                ->whereNotIn('seat_number', $desiredSeatNumbers)
                ->exists();

            if ($conflictingSeat) {
                throw ValidationException::withMessages([
                    'classes' => "Cannot change seat layout for {$flightClass->class} because booked/held seats would be removed.",
                ]);
            }

            $flightClass->seats()
                ->where('status', 'available')
                ->whereNotIn('seat_number', $desiredSeatNumbers)
                ->delete();

            $existingNumbers = $flightClass->seats()->pluck('seat_number')->all();
            $newSeats = [];
            $now = now();

            foreach ($desiredSeatNumbers as $seatNumber) {
                if (! in_array($seatNumber, $existingNumbers, true)) {
                    $newSeats[] = [
                        'flight_class_id' => $flightClass->id,
                        'seat_number'     => $seatNumber,
                        'status'          => 'available',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }

            if (! empty($newSeats)) {
                Seat::insert($newSeats);
            }
        }

        $classesToDelete = $flight->flightClasses()->whereNotIn('id', $keptClassIds)->get();
        foreach ($classesToDelete as $oldClass) {
            if ($oldClass->seats()->whereIn('status', ['booked', 'held'])->exists()) {
                throw ValidationException::withMessages([
                    'classes' => "Cannot remove {$oldClass->class} class because it has booked or held seats.",
                ]);
            }
            $oldClass->seats()->delete();
            $oldClass->delete();
        }
    }
}
