<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Route, Http, Storage, DB};
use Tests\Support\RouteFixtures;
use Tests\TestCase;

class AllRoutesTest extends TestCase
{
    use RefreshDatabase;

    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Storage::fake('local');
        config(['services.dev3_ai.endpoint' => null, 'services.dev3_ai.key' => null]);
        $this->fixtures = RouteFixtures::create();
    }

    private function url($route): string
    {
        $params = ['id' => 1, 'code' => $this->fixtures['booking']->code, 'flight' => $this->fixtures['flights'][0]->id,
            'country' => $this->fixtures['country']->id, 'airport' => $this->fixtures['airport']->id,
            'customer' => $this->fixtures['customer']->id, 'bookingFlight' => $this->fixtures['segment']->id,
            'conversation' => $this->fixtures['conversation']->id, 'locale' => 'ar', 'path' => 'qa-missing-file',
            'airlineAdmin' => $this->fixtures['airlineUser']->airlineAdmin->id, 'review' => $this->fixtures['review']->id,
            'message' => $this->fixtures['message']->id, 'number' => 'QA-MISSING-TICKET',
            'city' => $this->fixtures['airport']->city_id, 'booking' => $this->fixtures['pending']->id];
        return '/'.preg_replace_callback('/\{([^}]+)\}/', fn ($m) => $params[$m[1]], $route->uri());
    }

    public function test_every_get_route_with_the_expected_role(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (!in_array('GET', $route->methods())) continue;
            auth()->forgetGuards();
            $middleware = $route->gatherMiddleware();
            $role = in_array('role.super-admin', $middleware) ? 'admin' : (in_array('role.airline-admin', $middleware) ? 'airlineUser' : (in_array('role.customer', $middleware) ? 'user' : null));
            if ($role) $this->actingAs($this->fixtures[$role]);
            $expected = match ($route->getName()) {
                'my.review', 'book.payment.return', 'book.passengers', 'book.seats' => 302,
                'book.review', 'book.confirmation', 'my.ticket' => 404,
                'storage.local' => 403,
                default => str_starts_with($route->uri(), 'destinations/{') ? 302 : 200,
            };
            $response = $this->get($this->url($route));
            $this->assertSame($expected, $response->status(), $route->uri().' rendered unexpected status: '.substr(strip_tags($response->getContent()), 0, 250));
            $checked++;
        }
        $this->assertGreaterThan(50, $checked);
    }

    public function test_every_protected_route_rejects_guests_and_wrong_roles(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();
            $role = array_values(array_intersect(['role.super-admin', 'role.airline-admin', 'role.customer', 'auth'], array_filter($middleware, 'is_string')))[0] ?? null;
            if (!$role) continue;
            $method = array_values(array_diff($route->methods(), ['HEAD']))[0];
            auth()->forgetGuards();
            $response = $this->call($method, $this->url($route));
            $this->assertSame(302, $response->status(), $method.' '.$route->uri().' guest access');
            if ($role !== 'auth') {
                $this->actingAs($this->fixtures[$role === 'role.customer' ? 'admin' : 'user']);
                $response = $this->call($method, $this->url($route));
                $this->assertSame(403, $response->status(), $method.' '.$route->uri().' wrong-role access');
            }
            $checked++;
        }
        $this->assertGreaterThan(40, $checked);
    }

    public function test_every_mutating_route_handles_invalid_input_without_server_errors(): void
    {
        foreach (Route::getRoutes() as $route) {
            foreach (array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) as $method) {
                // RedirectController matches all verbs; GET is checked above.
                if (str_starts_with($route->uri(), 'destinations/{')) continue;
                auth()->forgetGuards();
                $middleware = $route->gatherMiddleware();
                if (in_array('role.super-admin', $middleware)) $this->actingAs($this->fixtures['admin']);
                if (in_array('role.customer', $middleware) || in_array('auth', $middleware)) $this->actingAs($this->fixtures['user']);
                if (in_array('role.airline-admin', $middleware)) $this->actingAs($this->fixtures['airlineUser']);
                $expected = match ($route->getName()) { 'stripe.webhook' => 400, 'storage.local.upload' => 403, 'my.booking.cancel' => 409, default => 302 };
                DB::beginTransaction();
                try {
                    $this->call($method, $this->url($route))->assertStatus($expected);
                } finally {
                    DB::rollBack();
                }
            }
        }
    }
}
