<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\CountryController;
use App\Models\Country;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\PresenceVerifierInterface;
use Illuminate\Validation\ValidationException;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OmarCountriesTest extends TestCase
{
    public function test_every_action_rejects_guests_and_non_admin_users(): void
    {
        $customer = new User;
        $customer->setRelation('superAdmin', null);

        foreach ([null, $customer] as $user) {
            $request = Request::create('/admin/countries');
            $request->setUserResolver(fn () => $user);

            foreach (['index', 'create', 'store', 'edit', 'update', 'destroy'] as $method) {
                try {
                    (new CountryController)->$method($request, new Country);
                    $this->fail("Unauthorized access to {$method} was allowed.");
                } catch (HttpException $exception) {
                    $this->assertSame(403, $exception->getStatusCode());
                }
            }
        }
    }

    public function test_admin_can_open_create_and_edit_forms(): void
    {
        $controller = new CountryController;
        $country = new Country(['name' => 'Jordan', 'code' => 'JO']);

        $create = $controller->create($this->adminRequest());
        $this->assertSame('super-admin.countries.create', $create->name());
        $this->assertFalse($create->getData()['country']->exists);

        $edit = $controller->edit($this->adminRequest(), $country);
        $this->assertSame('super-admin.countries.edit', $edit->name());
        $this->assertSame($country, $edit->getData()['country']);
    }

    public function test_required_fields_are_validated_before_writing(): void
    {
        try {
            (new CountryController)->store($this->adminRequest());
            $this->fail('Empty input was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame(['name', 'code', 'currency', 'language'], array_keys($exception->errors()));
        }
    }

    public function test_invalid_types_and_lengths_are_rejected(): void
    {
        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $presence->shouldReceive('getCount')->andReturn(0);
        app('validator')->setPresenceVerifier($presence);

        try {
            (new CountryController)->store($this->adminRequest([
                'name' => str_repeat('a', 101),
                'code' => '123',
                'currency' => str_repeat('a', 11),
                'language' => str_repeat('a', 61),
                'local_apps' => ['unexpected array'],
            ]));
            $this->fail('Invalid input was accepted.');
        } catch (ValidationException $exception) {
            $this->assertCount(5, $exception->errors());
        }
    }

    public function test_codes_are_normalized_before_duplicate_validation(): void
    {
        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $presence->shouldReceive('getCount')->once()->with('countries', 'name', 'Jordan', null, 'id', [])->andReturn(0);
        $presence->shouldReceive('getCount')->once()->with('countries', 'code', 'JO', null, 'id', [])->andReturn(1);
        app('validator')->setPresenceVerifier($presence);
        $request = $this->adminRequest(['name' => ' Jordan ', 'code' => ' jo ', 'currency' => 'JOD', 'language' => 'Arabic']);

        try {
            (new CountryController)->store($request);
            $this->fail('Duplicate code was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
            $this->assertSame('JO', $request->code);
            $this->assertSame('Jordan', $request->name);
        }
    }

    public function test_update_uniqueness_excludes_only_the_bound_country(): void
    {
        $country = new Country;
        $country->id = 7;
        $presence = Mockery::mock(PresenceVerifierInterface::class);
        $presence->shouldReceive('getCount')->once()->with('countries', 'name', 'Jordan', '7', 'id', [])->andReturn(1);
        $presence->shouldReceive('getCount')->once()->with('countries', 'code', 'JO', '7', 'id', [])->andReturn(0);
        app('validator')->setPresenceVerifier($presence);

        try {
            (new CountryController)->update($this->adminRequest([
                'name' => 'Jordan', 'code' => 'JO', 'currency' => 'JOD', 'language' => 'Arabic', 'id' => 999,
            ]), $country);
            $this->fail('Duplicate name was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
        }
    }

    public function test_country_templates_compile_to_valid_php(): void
    {
        $templates = glob(resource_path('views/super-admin/countries/*.blade.php'));
        $this->assertCount(5, $templates);

        foreach ($templates as $template) {
            $compiled = app('blade.compiler')->compileString(file_get_contents($template));
            $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
        }
    }

    public function test_proposed_routes_have_valid_syntax_and_existing_actions(): void
    {
        $routes = file_get_contents(base_path('routes/omar-routes.txt'));
        $this->assertNotEmpty(token_get_all("<?php\n".$routes, TOKEN_PARSE));
        preg_match_all("/CountryController::class, '([a-z]+)'/", $routes, $matches);
        $this->assertCount(6, $matches[1]);

        foreach ($matches[1] as $method) {
            $this->assertTrue((new \ReflectionMethod(CountryController::class, $method))->isPublic());
        }
    }

    private function adminRequest(array $data = []): Request
    {
        $user = new User;
        $user->setRelation('superAdmin', new SuperAdmin);
        $request = Request::create('/admin/countries', 'POST', $data);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
