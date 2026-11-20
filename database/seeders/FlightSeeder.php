<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Services\FlightInventoryService;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(FlightInventoryService $service): void
    {
        $jordan = Country::firstOrCreate(
            ['code' => 'JO'],
            ['name' => 'Jordan', 'currency' => 'JOD', 'language' => 'ar']
        );

        $uae = Country::firstOrCreate(
            ['code' => 'AE'],
            ['name' => 'UAE', 'currency' => 'AED', 'language' => 'ar']
        );

        $amman = City::firstOrCreate(['name' => 'Amman', 'country_id' => $jordan->id]);
        $dubai = City::firstOrCreate(['name' => 'Dubai', 'country_id' => $uae->id]);

        $amm = Airport::firstOrCreate(
            ['code' => 'AMM'],
            ['name' => 'Queen Alia International Airport', 'city_id' => $amman->id]
        );

        $dxb = Airport::firstOrCreate(
            ['code' => 'DXB'],
            ['name' => 'Dubai International Airport', 'city_id' => $dubai->id]
        );

        $airline = Airline::firstOrCreate(
            ['code' => 'RJ'],
            ['name' => 'Royal Jordanian', 'country' => 'Jordan', 'status' => 'active']
        );

        $service->createFlight($airline->id, [
            'flight_number'          => 'RJ101',
            'origin_airport_id'      => $amm->id,
            'destination_airport_id' => $dxb->id,
            'departure_at'           => now()->addDays(3)->setTime(10, 0),
            'arrival_at'             => now()->addDays(3)->setTime(13, 30),
            'stops'                  => 0,
            'status'                 => 'scheduled',
        ], [
            [
                'class'         => 'economy',
                'price'         => 150.00,
                'seat_rows'     => 5,
                'seats_per_row' => 6,
            ],
        ]);
    }
}
