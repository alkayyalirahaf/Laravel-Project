<?php

namespace Database\Seeders;

use App\Models\Flight;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Explicitly invoked fixture for local browser walkthroughs. */
class BrowserFlowSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Browser fixtures are restricted to local and testing environments.');
        }

        $user = User::firstOrCreate(
            ['email' => 'browser.flow@example.test'],
            ['name' => 'Browser Flow Tester', 'password' => 'FlyNow-Test-2026!'],
        );
        $user->customer()->firstOrCreate([], ['credit_balance' => '1000.00']);

        if (! Flight::where('flight_number', 'RJ101')->where('departure_at', '>', now())->exists()) {
            $this->call(FlightSeeder::class);
        }
    }
}
