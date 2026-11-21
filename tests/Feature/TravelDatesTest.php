<?php

namespace Tests\Feature;

use App\Services\FlightSearchService;
use Carbon\Carbon;
use Database\Seeders\FlightSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TravelDatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_rejects_a_date_that_is_yesterday_in_amman_even_before_utc_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-11-21 22:30:00', 'UTC'));
        $this->seed(FlightSeeder::class);
        try {
            app(FlightSearchService::class)->validateFilters(['from' => 'AMM', 'to' => 'DXB', 'depart' => '2026-11-21', 'trip' => 'one_way']);
            $this->fail('A past travel date should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('depart', $exception->errors());
        }
        $this->assertSame('2026-11-22', app(FlightSearchService::class)->validateFilters(['from' => 'AMM', 'to' => 'DXB', 'depart' => '2026-11-22', 'trip' => 'one_way'])['depart']);
    }

    public function test_return_before_departure_is_rejected(): void
    {
        $this->seed(FlightSeeder::class);
        try {
            app(FlightSearchService::class)->validateFilters(['from' => 'AMM', 'to' => 'DXB', 'depart' => now('Asia/Amman')->addDays(5)->toDateString(), 'return' => now('Asia/Amman')->addDays(3)->toDateString(), 'trip' => 'round_trip']);
            $this->fail('Return before departure should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('return', $exception->errors());
        }
    }
}
