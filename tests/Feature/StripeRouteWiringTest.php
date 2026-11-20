<?php

namespace Tests\Feature;

use App\Models\Airline;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeRouteWiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_payment_page(): void
    {
        $this->get(route('book.payment'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_can_open_the_payment_page(): void
    {
        config()->set('services.stripe.key', 'pk_test_route');
        [$user, $booking] = $this->makeCustomerBooking();

        $this->actingAs($user)
            ->get(route('book.payment', ['booking_id' => $booking->id]))
            ->assertOk()
            ->assertViewIs('booking.payment')
            ->assertSee('Booking '.$booking->code)
            ->assertSee('JOD 20.00')
            ->assertSee('28.21 USD at 1 JOD = 1.41 USD')
            ->assertSee('action="'.route('book.payment.intent').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Pay with Stripe')
            ->assertDontSee('payment-element');
    }

    public function test_airline_admin_is_forbidden_from_the_customer_payment_page(): void
    {
        $user = User::create([
            'name' => 'Airline Admin',
            'email' => 'airline-admin@example.test',
            'password' => 'password',
        ]);
        $airline = Airline::create([
            'name' => 'Test Airline',
            'code' => 'TST',
            'country' => 'Jordan',
            'status' => 'active',
        ]);
        $user->airlineAdmin()->create(['airline_id' => $airline->id]);

        $this->actingAs($user)
            ->get(route('book.payment'))
            ->assertForbidden();
    }

    public function test_webhook_is_reachable_without_login_and_rejects_a_bad_signature(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_route');

        $this->postJson(route('stripe.webhook'), [], [
            'Stripe-Signature' => 't='.time().',v1=invalid',
        ])->assertStatus(400);

        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_the_payment_intent_endpoint(): void
    {
        $this->postJson(route('book.payment.intent'), ['booking_id' => 1])
            ->assertRedirect(route('login'));
    }

    /** @return array{User, Booking} */
    private function makeCustomerBooking(): array
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@example.test',
            'password' => 'password',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'credit_balance' => '0.00',
        ]);
        $booking = Booking::create([
            'code' => 'BKROUTE0001',
            'customer_id' => $customer->id,
            'trip_type' => 'one_way',
            'status' => 'pending',
            'subtotal' => '20.00',
            'taxes' => '0.00',
            'fees' => '0.00',
            'total' => '20.00',
            'credits_used' => '0.00',
            'amount_paid' => '0.00',
            'contact_email' => $user->email,
        ]);

        return [$user, $booking];
    }
}
