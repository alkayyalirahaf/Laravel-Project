<?php

namespace Tests\Feature;

use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;
use Tests\TestCase;

class StripePaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_passes_booking_summary_amount_to_dedicated_view(): void
    {
        [$user, , $booking] = $this->makeCustomerAndBooking(total: '12.34', creditsUsed: '2.34');
        $request = Request::create('/book/payment', 'GET', ['booking_id' => $booking->id]);
        $request->setUserResolver(fn () => $user);

        $view = app(PaymentController::class)->show($request, new PaymentService);

        $this->assertSame('booking.payment', $view->name());
        $this->assertSame('10.00', $view->getData()['amountDueJod']);
        $this->assertSame('14.10', $view->getData()['amountDueUsd']);
        $this->assertSame('1.41044', $view->getData()['jodToUsdRate']);
        $this->assertSame($booking->id, $view->getData()['booking']->id);
    }

    public function test_fully_credit_paid_booking_skips_stripe_and_remains_pending_without_finalizer(): void
    {
        [$customerUser, $customer, $booking] = $this->makeCustomerAndBooking(total: '12.00', creditsUsed: '12.00');
        $paymentService = new PaymentService(new StripeClient('sk_test_mock'));

        $session = $paymentService->createCheckoutSession($booking, $customer);

        $this->assertNull($session);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertNotNull($customerUser);
        $this->assertSame('0.00', $customer->fresh()->credit_balance);
        $this->assertDatabaseCount('credit_transactions', 1);
        $this->assertNull($paymentService->createCheckoutSession($booking, $customer));
        $this->assertDatabaseCount('credit_transactions', 1);
    }

    public function test_checkout_rejects_unfunded_credit_usage_without_creating_payment(): void
    {
        [, $customer, $booking] = $this->makeCustomerAndBooking(total: '12.00', creditsUsed: '12.00');
        $customer->update(['credit_balance' => '1.00']);
        try {
            (new PaymentService(new StripeClient('sk_test_mock')))->createCheckoutSession($booking, $customer);
            $this->fail('Unfunded credits were accepted.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertSame('1.00', $customer->fresh()->credit_balance);
        $this->assertDatabaseCount('credit_transactions', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_success_webhook_marks_payment_once_when_the_event_is_repeated(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_secret');
        [, , $booking] = $this->makeCustomerAndBooking();
        $payment = $this->makePayment($booking, 'pi_success_123');
        $payload = $this->eventPayload('evt_success_1', 'payment_intent.succeeded', 'pi_success_123', $booking);

        $first = $this->sendSignedWebhook($payload);
        $this->assertSame(200, $first->getStatusCode());
        $this->assertSame(['received' => true], json_decode($first->getContent(), true));
        $paidAt = $payment->fresh()->paid_at;
        $receipt = $payment->fresh()->receipt_number;

        $repeated = $this->sendSignedWebhook($payload);
        $this->assertSame(200, $repeated->getStatusCode());
        $this->assertSame(['received' => true], json_decode($repeated->getContent(), true));

        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertNotNull($paidAt);
        $this->assertSame($paidAt->toDateTimeString(), $payment->fresh()->paid_at->toDateTimeString());
        $this->assertSame($receipt, $payment->fresh()->receipt_number);
    }

    public function test_bad_webhook_signature_is_rejected_with_400(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_secret');
        [, , $booking] = $this->makeCustomerAndBooking();
        $payload = json_encode($this->eventPayload('evt_bad_sig', 'payment_intent.succeeded', 'pi_bad_sig', $booking), JSON_THROW_ON_ERROR);
        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=invalid',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response = app(StripeWebhookController::class)->handle($request, new PaymentService);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_failed_webhook_marks_payment_failed(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_secret');
        [, , $booking] = $this->makeCustomerAndBooking();
        $payment = $this->makePayment($booking, 'pi_failed_123');

        $response = $this->sendSignedWebhook($this->eventPayload(
            'evt_failed_1',
            'payment_intent.payment_failed',
            'pi_failed_123',
            $booking,
        ));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_unknown_signed_webhook_event_is_acknowledged_without_payment_changes(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_test_secret');
        [, , $booking] = $this->makeCustomerAndBooking();
        $payment = $this->makePayment($booking, 'pi_unknown_123');

        $response = $this->sendSignedWebhook($this->eventPayload(
            'evt_unknown_1',
            'customer.created',
            'pi_unknown_123',
            $booking,
        ));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['received' => true], json_decode($response->getContent(), true));
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_payment_tables_have_no_card_number_or_cvc_columns(): void
    {
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");

        foreach ($tables as $table) {
            foreach (Schema::getColumnListing($table->name) as $column) {
                $this->assertDoesNotMatchRegularExpression('/card|cvc|security_code/i', $column);
            }
        }

        $paymentView = file_get_contents(resource_path('views/booking/payment.blade.php'));
        $this->assertIsString($paymentView);
        $this->assertStringNotContainsString('card_number', $paymentView);
        $this->assertStringNotContainsString('cvc', $paymentView);
    }

    private function sendSignedWebhook(array $payload): JsonResponse
    {
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawPayload, 'whsec_test_secret');
        $request = Request::create('/stripe/webhook', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawPayload);

        return app(StripeWebhookController::class)->handle($request, new PaymentService);
    }

    private function eventPayload(string $eventId, string $type, string $paymentIntentId, Booking $booking): array
    {
        $paymentService = new PaymentService;

        return [
            'id' => $eventId,
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'type' => $type,
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'object' => 'payment_intent',
                    'status' => $type === 'payment_intent.succeeded' ? 'succeeded' : 'requires_payment_method',
                    'amount' => $paymentService->chargeAmountCents($booking),
                    'currency' => $paymentService->currency(),
                    'metadata' => [
                        'booking_id' => (string) $booking->id,
                        'customer_id' => (string) $booking->customer_id,
                        'amount_due_jod' => $paymentService->amountDueJod($booking),
                        'amount_usd' => $paymentService->chargeAmountUsd($booking),
                        'jod_to_usd_rate' => $paymentService->jodToUsdRate(),
                    ],
                ],
            ],
        ];
    }

    /** @return array{User, Customer, Booking} */
    private function makeCustomerAndBooking(
        string $email = 'traveler@example.test',
        string $total = '100.00',
        string $creditsUsed = '0.00',
        string $status = 'pending',
    ): array {
        $user = User::create([
            'name' => 'Test Traveler',
            'email' => $email,
            'password' => 'password',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'credit_balance' => $creditsUsed,
        ]);
        $booking = Booking::create([
            'code' => sprintf('BK%010d', Booking::count() + 1),
            'customer_id' => $customer->id,
            'trip_type' => 'one_way',
            'status' => $status,
            'subtotal' => $total,
            'taxes' => '0.00',
            'fees' => '0.00',
            'total' => $total,
            'credits_used' => $creditsUsed,
            'amount_paid' => '0.00',
            'contact_email' => $email,
        ]);

        return [$user, $customer, $booking];
    }

    private function makePayment(Booking $booking, string $paymentIntentId): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => $paymentIntentId,
            'amount' => $booking->total,
            'status' => 'pending',
        ]);
    }
}
