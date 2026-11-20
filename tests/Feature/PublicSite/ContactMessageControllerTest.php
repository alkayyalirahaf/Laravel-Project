<?php

namespace Tests\Feature\PublicSite;

use App\Http\Controllers\PublicSite\ContactMessageController;
use App\Http\Requests\PublicSite\ContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContactMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_submission_is_stored_with_new_status_and_no_customer(): void
    {
        $payload = $this->validPayload() + ['status' => 'handled', 'customer_id' => 999999];
        $request = $this->contactRequest($payload);
        $response = app(ContactMessageController::class)->store($request, app(AuthService::class));

        $message = ContactMessage::firstOrFail();

        $this->assertSame('new', $message->status);
        $this->assertNull($message->customer_id);
        $this->assertSame('suggestion', $message->type);
        $this->assertSame('Thank you. Your message has been sent.', $response->getSession()->get('status'));
    }

    public function test_customer_submission_links_the_customer_profile(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['user_id' => $user->id, 'credit_balance' => '0.00']);
        $request = $this->contactRequest($this->validPayload(), $user);

        app(ContactMessageController::class)->store($request, app(AuthService::class));

        $this->assertDatabaseHas('contact_messages', [
            'customer_id' => $customer->id,
            'name' => 'A Traveler',
            'email' => 'traveler@example.test',
            'status' => 'new',
        ]);
    }

    public function test_contact_request_reports_field_level_validation_errors(): void
    {
        try {
            $this->contactRequest(['name' => '', 'email' => 'not-an-email', 'type' => '', 'message' => '']);
            $this->fail('Invalid contact data should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('name', $exception->errors());
            $this->assertArrayHasKey('email', $exception->errors());
            $this->assertArrayHasKey('type', $exception->errors());
            $this->assertArrayHasKey('message', $exception->errors());
        }
    }

    public function test_contact_request_rejects_an_invalid_type(): void
    {
        try {
            $this->contactRequest($this->validPayload(['type' => 'refund']));
            $this->fail('An unsupported contact type should fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('type', $exception->errors());
        }
    }

    public function test_non_customer_user_is_not_linked_as_a_customer(): void
    {
        $user = User::factory()->create();
        $user->superAdmin()->create();
        $request = $this->contactRequest($this->validPayload(), $user);

        app(ContactMessageController::class)->store($request, app(AuthService::class));

        $this->assertNull(ContactMessage::firstOrFail()->customer_id);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'A Traveler',
            'email' => 'traveler@example.test',
            'type' => 'suggestion',
            'message' => 'Please add an earlier flight.',
        ], $overrides);
    }

    private function contactRequest(array $payload, ?User $user = null): ContactMessageRequest
    {
        /** @var ContactMessageRequest $request */
        $request = ContactMessageRequest::create('/contact', 'POST', $payload);
        $request->setContainer(app());
        $request->setRedirector(app(Redirector::class));
        $request->setUserResolver(fn () => $user);

        $request->validateResolved();

        return $request;
    }
}
