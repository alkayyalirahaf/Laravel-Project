<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // Sample development data; agree on the list with the team before running.
        DB::transaction(function () {
            foreach ([
                ['code' => 'JO', 'name' => 'Jordan', 'currency' => 'JOD', 'language' => 'Arabic'],
                ['code' => 'AE', 'name' => 'United Arab Emirates', 'currency' => 'AED', 'language' => 'Arabic'],
                ['code' => 'EG', 'name' => 'Egypt', 'currency' => 'EGP', 'language' => 'Arabic'],
            ] as $country) {
                Country::firstOrCreate(['code' => $country['code']], $country);
            }
        });
    }
}
