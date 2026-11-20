<?php

namespace Tests\Feature\SuperAdmin;

use App\Http\Controllers\SuperAdmin\AirportController;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Requests\SuperAdmin\StoreAirportRequest;
use App\Http\Requests\SuperAdmin\UpdateAirportRequest;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Flight;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AirportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_airport_and_uppercases_its_code(): void
    {
        $city = $this->makeCity();
        $request = $this->validatedRequest(StoreAirportRequest::class, [
            'code' => 'amm',
            'name' => 'Queen Alia International',
            'city_id' => $city->id,
        ]);

        $response = app(AirportController::class)->store($request);

        $this->assertDatabaseHas('airports', [
            'code' => 'AMM',
            'name' => 'Queen Alia International',
            'city_id' => $city->id,
        ]);
        $this->assertSame(route('admin.airports'), $response->getTargetUrl());
    }

    public function test_store_rejects_duplicate_code(): void
    {
        $city = $this->makeCity();
        $this->makeAirport($city, 'AMM');

        $this->assertFieldValidationError('code', fn () => $this->validatedRequest(StoreAirportRequest::class, [
            'code' => 'amm',
            'name' => 'Duplicate Airport',
            'city_id' => $city->id,
        ]));
    }

    public function test_store_rejects_a_city_that_does_not_exist(): void
    {
        $this->assertFieldValidationError('city_id', fn () => $this->validatedRequest(StoreAirportRequest::class, [
            'code' => 'AMM',
            'name' => 'Queen Alia International',
            'city_id' => 999999,
        ]));
    }

    public function test_update_allows_the_airports_existing_code(): void
    {
        $city = $this->makeCity();
        $airport = $this->makeAirport($city, 'AMM');
        $request = $this->validatedRequest(UpdateAirportRequest::class, [
            'code' => 'amm',
            'name' => 'Queen Alia Airport',
            'city_id' => $city->id,
        ], $airport);

        app(AirportController::class)->update($request, $airport);

        $this->assertDatabaseHas('airports', [
            'id' => $airport->id,
            'code' => 'AMM',
            'name' => 'Queen Alia Airport',
        ]);
    }

    #[DataProvider('flightEndpointCases')]
    public function test_delete_is_blocked_if_flights_use_airport_as_either_endpoint(bool $airportIsOrigin): void
    {
        $city = $this->makeCity();
        $airport = $this->makeAirport($city, 'AMM');
        $otherAirport = $this->makeAirport($city, 'DXB');
        $airline = Airline::create([
            'name' => 'Test Airline',
            'code' => 'TST',
            'country' => 'Jordan',
            'status' => 'active',
        ]);

        Flight::create([
            'airline_id' => $airline->id,
            'flight_number' => 'YT100',
            'origin_airport_id' => $airportIsOrigin ? $airport->id : $otherAirport->id,
            'destination_airport_id' => $airportIsOrigin ? $otherAirport->id : $airport->id,
            'departure_at' => now()->addDay(),
            'arrival_at' => now()->addDay()->addHours(2),
            'stops' => 0,
            'status' => 'scheduled',
        ]);

        $response = app(AirportController::class)->destroy($airport);

        $this->assertDatabaseHas('airports', ['id' => $airport->id]);
        $this->assertStringContainsString('cannot be deleted', $response->getSession()->get('status'));
    }

    public function test_index_searches_name_and_code_filters_by_city_and_paginates(): void
    {
        $city = $this->makeCity('Amman');
        $otherCity = $this->makeCity('Aqaba');

        for ($index = 0; $index < 16; $index++) {
            $letters = chr(65 + intdiv($index, 26)).chr(65 + ($index % 26));
            $this->makeAirport($city, 'A'.$letters, 'Search Airport '.$index);
        }

        $this->makeAirport($otherCity, 'ZZZ', 'Search Airport outside city');
        $request = Request::create('/admin/airports?search=Search&city_id='.$city->id);
        $view = app(AirportController::class)->index($request);
        $airports = $view->getData()['airports'];

        $this->assertSame(16, $airports->total());
        $this->assertCount(15, $airports->items());
        $this->assertSame((string) $city->id, (string) $view->getData()['filters']['city_id']);
    }

    public function test_non_super_admin_is_forbidden_by_existing_middleware(): void
    {
        $user = User::factory()->create();
        Customer::create(['user_id' => $user->id, 'credit_balance' => '0.00']);
        $request = Request::create('/admin/airports');
        $request->setUserResolver(fn () => $user);
        $middleware = new EnsureSuperAdmin(app(AuthService::class));

        try {
            $middleware->handle($request, fn () => new Response('ok'));
            $this->fail('A customer must not pass super-admin middleware.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function makeCity(string $name = 'Amman'): City
    {
        $country = Country::firstOrCreate(
            ['code' => 'JO'],
            ['name' => 'Jordan', 'currency' => 'JOD', 'language' => 'Arabic'],
        );

        return City::create(['country_id' => $country->id, 'name' => $name]);
    }

    private function makeAirport(City $city, string $code, string $name = 'Test Airport'): Airport
    {
        return Airport::create(['city_id' => $city->id, 'code' => $code, 'name' => $name]);
    }

    /** @param class-string<FormRequest> $requestClass */
    private function validatedRequest(string $requestClass, array $data, ?Airport $airport = null): FormRequest
    {
        $method = $airport === null ? 'POST' : 'PUT';
        $request = $requestClass::create('/admin/airports', $method, $data);
        $request->setContainer(app());
        $request->setRedirector(app(Redirector::class));

        if ($airport !== null) {
            $route = new class($airport)
            {
                public function __construct(private readonly Airport $airport) {}

                public function parameter(string $name, mixed $default = null): mixed
                {
                    return $name === 'airport' ? $this->airport : $default;
                }
            };

            $request->setRouteResolver(fn () => $route);
        }

        $request->validateResolved();

        return $request;
    }

    private function assertFieldValidationError(string $field, callable $action): void
    {
        try {
            $action();
            $this->fail("Expected a validation error for {$field}.");
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
    }

    public static function flightEndpointCases(): array
    {
        return [
            'origin airport' => [true],
            'destination airport' => [false],
        ];
    }
}
