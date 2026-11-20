<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'JO' => 'Amman',
            'AE' => 'Dubai',
            'EG' => 'Cairo',
        ];

        DB::transaction(function () use ($cities): void {
            foreach ($cities as $code => $name) {
                $country = Country::where('code', $code)->first();

                if ($country === null) {
                    throw new RuntimeException("Country {$code} is missing. Run CountrySeeder first.");
                }

                $country->cities()->firstOrCreate(['name' => $name]);
            }
        });
    }
}
