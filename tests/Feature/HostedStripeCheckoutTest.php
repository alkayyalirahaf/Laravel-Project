<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingFinalizationService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiErrorException;
use Stripe\HttpClient\ClientInterface;
use Stripe\StripeClient;
use Tests\TestCase;

class HostedStripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ?ClientInterface $previousStripeHttpClient = null;

    /** @var array<int, array{method: string, url: string, headers: array, params: array}> */
    private array $stripeRequests = [];

    /** @var array<int, int> */
    private array $stripeTransactionLevels = [];

    protected function tearDown(): void
    {
        if ($this->previousStripeHttpClient !== null) {
            ApiRequestor::setHttpClient($this->previousStripeHttpClient);
        }

        parent::tearDown();
    }

    public function test_checkout_rejects_another_customers_booking(): void
    {
        [, , $booking] = $this->makeCustomerBooking();
        [$otherUser] = $this->makeCustomerBooking('other@example.test');

        $this->actingAs($otherUser)
            ->post(route('book.payment.intent'), ['booking_id' => $booking->id])
            ->assertNotFound();
    }

    public function test_checkout_rejects_a_non_pending_booking(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking(status: 'confirmed');

        $this->actingAs($user)
            ->post(route('book.payment.intent'), ['booking_id' => $booking->id])
            ->assertStatus(409);
    }

    public function test_checkout_amount_and_line_item_are_computed_from_booking_data(): void
    {
        config()->set('services.stripe.currency', 'jod');
        [$user, , $booking] = $this->makeCustomerBooking(total: '120.00', creditsUsed: '5.00');
        $transactionLevel = DB::transactionLevel();
        $this->mockStripeResponses([$this->checkoutSessionResponse(
            'cs_test_new',
            'https://checkout.stripe.com/c/pay/cs_test_new',
            'unpaid',
            null,
            $booking,
        )]);

        $this->actingAs($user)
            ->post(route('book.payment.intent'), [
                'booking_id' => $booking->id,
                'amount' => 0.01,
                'currency' => 'usd',
                'status' => 'paid',
                'redirect_url' => 'https://attacker.invalid/',
            ])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_new');

        $params = $this->stripeRequests[0]['params'];
        $this->assertSame('payment', $params['mode']);
        $this->assertSame(16220, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('usd', $params['line_items'][0]['price_data']['currency']);
        $this->assertSame('Yalla Teer booking '.$booking->code, $params['line_items'][0]['price_data']['product_data']['name']);
        $this->assertSame((string) $booking->id, $params['metadata']['booking_id']);
        $this->assertSame((string) $booking->customer_id, $params['metadata']['customer_id']);
        $this->assertSame('115.00', $params['metadata']['amount_due_jod']);
        $this->assertSame('162.20', $params['metadata']['amount_usd']);
        $this->assertSame('1.41044', $params['metadata']['jod_to_usd_rate']);
        $this->assertSame((string) $booking->id, $params['payment_intent_data']['metadata']['booking_id']);
        $this->assertSame($transactionLevel, $this->stripeTransactionLevels[0]);
        $this->assertStringContainsString('session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
        $this->assertStringContainsString(route('book.payment.return'), $params['success_url']);
        $this->assertStringContainsString('cancelled=1', $params['cancel_url']);

        // Session IDs are deliberately not persisted; payment rows start on verified payment.
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_checkout_conversion_rounds_half_up_to_currency_cents(): void
    {
        config()->set('services.stripe.jod_rate', '1.415');
        [$user, , $booking] = $this->makeCustomerBooking(total: '1.00');
        $this->mockStripeResponses([$this->checkoutSessionResponse(
            'cs_test_rounding',
            'https://checkout.stripe.com/c/pay/cs_test_rounding',
            'unpaid',
            null,
            $booking,
        )]);

        $this->actingAs($user)
            ->post(route('book.payment.intent'), ['booking_id' => $booking->id])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_rounding');

        $params = $this->stripeRequests[0]['params'];
        $this->assertSame(142, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('1.42', $params['metadata']['amount_usd']);
        $this->assertSame('1.415', $params['metadata']['jod_to_usd_rate']);
    }

    public function test_return_marks_paid_once_then_the_same_session_webhook_is_a_no_op(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_checkout_test');
        $this->mockBookingFinalizerToConfirm();
        [$user, , $booking] = $this->makeCustomerBooking(total: '115.00');
        $session = $this->checkoutSessionResponse(
            'cs_test_paid',
            'https://checkout.stripe.com/c/pay/cs_test_paid',
            'paid',
            'pi_checkout_paid',
            $booking,
        );
        $this->mockStripeResponses([$session, $session]);

        $returnUrl = route('book.payment.return', ['session_id' => 'cs_test_paid']);
        $this->actingAs($user)
            ->get($returnUrl)
            ->assertRedirect(route('book.confirmation', ['booking_id' => $booking->id]))
            ->assertSessionHas('status', 'Payment confirmed. Your booking is being finalized.');

        $payment = Payment::where('stripe_payment_intent_id', 'pi_checkout_paid')->firstOrFail();
        $receiptNumber = $payment->receipt_number;
        $paidAt = $payment->paid_at->toDateTimeString();

        $this->get($returnUrl)
            ->assertRedirect(route('book.confirmation', ['booking_id' => $booking->id]));

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('115.00', $payment->fresh()->amount);
        $this->assertSame($receiptNumber, $payment->fresh()->receipt_number);
        $this->assertSame($paidAt, $payment->fresh()->paid_at->toDateTimeString());

        $webhook = $this->checkoutCompletedEvent($session);
        $this->sendSignedWebhook($webhook)->assertOk()->assertExactJson(['received' => true]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame($receiptNumber, $payment->fresh()->receipt_number);
        $this->assertSame($paidAt, $payment->fresh()->paid_at->toDateTimeString());
    }

    public function test_unpaid_checkout_return_records_nothing(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking();
        $session = $this->checkoutSessionResponse(
            'cs_test_unpaid',
            'https://checkout.stripe.com/c/pay/cs_test_unpaid',
            'unpaid',
            null,
            $booking,
        );
        $this->mockStripeResponses([$session]);

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_test_unpaid']))
            ->assertRedirect(route('book.payment', ['booking_id' => $booking->id]))
            ->assertSessionHas('status', 'Payment was not completed. No payment was recorded; you can try again.');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_paid_return_with_a_charge_mismatch_does_not_record_payment(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking(total: '115.00');
        $session = $this->checkoutSessionResponse(
            'cs_test_mismatch',
            'https://checkout.stripe.com/c/pay/cs_test_mismatch',
            'paid',
            'pi_checkout_mismatch',
            $booking,
        );
        $session['amount_total']--;
        $this->mockStripeResponses([$session]);

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_test_mismatch']))
            ->assertRedirect(route('book.payment', ['booking_id' => $booking->id]))
            ->assertSessionHas('status', 'The Stripe charge could not be verified. No payment was recorded; contact support.');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_return_rejects_a_checkout_session_for_another_customer(): void
    {
        [$user] = $this->makeCustomerBooking('current@example.test');
        [, , $otherBooking] = $this->makeCustomerBooking('other@example.test');
        $session = $this->checkoutSessionResponse(
            'cs_test_other_customer',
            'https://checkout.stripe.com/c/pay/cs_test_other_customer',
            'paid',
            'pi_other_customer',
            $otherBooking,
        );
        $this->mockStripeResponses([$session]);

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_test_other_customer']))
            ->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_return_verifies_saved_quote_after_rate_configuration_changes(): void
    {
        config()->set('services.stripe.jod_rate', '1.41044');
        $this->mockBookingFinalizerToConfirm();
        [$user, , $booking] = $this->makeCustomerBooking(total: '115.00');
        $session = $this->checkoutSessionResponse(
            'cs_test_stable_return',
            'https://checkout.stripe.com/c/pay/cs_test_stable_return',
            'paid',
            'pi_stable_return',
            $booking,
        );
        $this->mockStripeResponses([$session]);
        config()->set('services.stripe.jod_rate', '1.75000');

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_test_stable_return']))
            ->assertRedirect(route('book.confirmation', ['booking_id' => $booking->id]));

        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_stable_return',
            'amount' => '115.00',
            'status' => 'succeeded',
        ]);
    }

    public function test_checkout_webhook_verifies_saved_quote_after_rate_configuration_changes(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_checkout_test');
        config()->set('services.stripe.jod_rate', '1.41044');
        $this->mockBookingFinalizerToConfirm();
        [, , $booking] = $this->makeCustomerBooking(total: '115.00');
        $session = $this->checkoutSessionResponse(
            'cs_test_stable_webhook',
            'https://checkout.stripe.com/c/pay/cs_test_stable_webhook',
            'paid',
            'pi_stable_webhook',
            $booking,
        );
        config()->set('services.stripe.jod_rate', '1.75000');

        $this->sendSignedWebhook($this->checkoutCompletedEvent($session))
            ->assertOk()
            ->assertExactJson(['received' => true]);

        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_stable_webhook',
            'amount' => '115.00',
            'status' => 'succeeded',
        ]);
    }

    public function test_second_successful_payment_for_a_booking_is_failed_and_refunded(): void
    {
        [, $customer, $booking] = $this->makeCustomerBooking();
        $existing = Payment::create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => 'pi_first_success',
            'amount' => '100.00',
            'status' => 'succeeded',
        ]);
        $this->mockStripeResponses([$this->refundResponse('re_second_success', 'pi_second_success', 10000)]);
        Log::spy();

        $result = (new PaymentService)->markPaid(
            'pi_second_success',
            (int) $booking->id,
            (int) $customer->id,
            '100.00',
        );

        $this->assertTrue($result);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('succeeded', $existing->fresh()->status);
        $this->assertSame('100.00', $existing->fresh()->amount);
        $this->assertSame('failed', Payment::where('stripe_payment_intent_id', 'pi_second_success')->value('status'));
        $this->assertRefundRequests('pi_second_success', 1);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_paid_intent_for_a_non_pending_booking_is_recorded_failed_and_refunded(): void
    {
        [, $customer, $booking] = $this->makeCustomerBooking(status: 'confirmed');
        $this->mockStripeResponses([$this->refundResponse('re_after_confirmation', 'pi_after_confirmation', 10000)]);

        $result = (new PaymentService)->markPaid(
            'pi_after_confirmation',
            (int) $booking->id,
            (int) $customer->id,
            '100.00',
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_after_confirmation',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        $this->assertRefundRequests('pi_after_confirmation', 1);
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_paid_return_for_cancelled_booking_refunds_and_repeated_return_uses_same_idempotency_key(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking(status: 'cancelled');
        $session = $this->checkoutSessionResponse(
            'cs_cancelled_paid',
            'https://checkout.stripe.com/c/pay/cs_cancelled_paid',
            'paid',
            'pi_cancelled_paid',
            $booking,
        );
        $refund = $this->refundResponse('re_cancelled_paid', 'pi_cancelled_paid', 10000);
        $this->mockStripeResponses([$session, $refund, $session, $refund]);
        Log::spy();
        $returnUrl = route('book.payment.return', ['session_id' => 'cs_cancelled_paid']);

        $this->actingAs($user)->get($returnUrl)
            ->assertRedirect(route('my.bookings'))
            ->assertSessionHas('status', 'Your payment was refunded because the booking could not be completed. See My Bookings for your reservations.');
        $this->get($returnUrl)->assertRedirect(route('my.bookings'));

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_cancelled_paid',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertRefundRequests('pi_cancelled_paid', 2);
        Log::shouldHaveReceived('error')->twice();
    }

    public function test_repeated_paid_webhook_refunds_cancelled_booking_with_the_same_idempotency_key(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_checkout_test');
        [, , $booking] = $this->makeCustomerBooking(status: 'cancelled');
        $session = $this->checkoutSessionResponse(
            'cs_cancelled_webhook',
            'https://checkout.stripe.com/c/pay/cs_cancelled_webhook',
            'paid',
            'pi_cancelled_webhook',
            $booking,
        );
        $refund = $this->refundResponse('re_cancelled_webhook', 'pi_cancelled_webhook', 10000);
        $this->mockStripeResponses([$refund, $refund]);

        $event = $this->checkoutCompletedEvent($session);
        $this->sendSignedWebhook($event)->assertOk()->assertExactJson(['received' => true]);
        $this->sendSignedWebhook($event)->assertOk()->assertExactJson(['received' => true]);

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_cancelled_webhook',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertRefundRequests('pi_cancelled_webhook', 2);
    }

    public function test_finalizer_exception_records_failed_payment_and_refunds(): void
    {
        [, $customer, $booking] = $this->makeCustomerBooking();
        $finalizer = Mockery::mock(BookingFinalizationService::class);
        $finalizer->shouldReceive('finalize')->once()->andThrow(new \RuntimeException('private finalizer detail'));
        app()->instance(BookingFinalizationService::class, $finalizer);
        $this->mockStripeResponses([$this->refundResponse('re_finalizer_error', 'pi_finalizer_error', 10000)]);
        Log::spy();

        $result = app(PaymentService::class)->markPaidWithOutcome(
            'pi_finalizer_error',
            (int) $booking->id,
            (int) $customer->id,
            '100.00',
        );

        $this->assertSame(PaymentService::PAID_REFUNDED, $result);
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_finalizer_error',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertRefundRequests('pi_finalizer_error', 1);
        Log::shouldHaveReceived('error')->once()->with(
            'Stripe payment could not be finalized; issuing a full refund.',
            Mockery::on(fn (array $context): bool => $context['booking_id'] === $booking->id
                && $context['stripe_payment_intent_id'] === 'pi_finalizer_error'
                && $context['reason'] === 'finalization_threw'
                && $context['exception_class'] === \RuntimeException::class),
        );
    }

    public function test_finalizer_that_returns_without_confirmation_causes_a_refund(): void
    {
        [, $customer, $booking] = $this->makeCustomerBooking();
        $this->mockStripeResponses([$this->refundResponse('re_no_confirmation', 'pi_no_confirmation', 10000)]);

        $result = app(PaymentService::class)->markPaidWithOutcome(
            'pi_no_confirmation',
            (int) $booking->id,
            (int) $customer->id,
            '100.00',
        );

        $this->assertSame(PaymentService::PAID_REFUNDED, $result);
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_no_confirmation',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertDatabaseCount('tickets', 0);
        $this->assertRefundRequests('pi_no_confirmation', 1);
    }

    public function test_refund_api_failure_is_logged_critically_and_return_shows_support_message(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking(status: 'cancelled');
        $session = $this->checkoutSessionResponse(
            'cs_refund_failure',
            'https://checkout.stripe.com/c/pay/cs_refund_failure',
            'paid',
            'pi_refund_failure',
            $booking,
        );
        $this->mockStripeResponses([
            $session,
            [
                '_http_status' => 400,
                'error' => ['type' => 'invalid_request_error', 'message' => 'Sensitive Stripe detail'],
            ],
        ]);
        Log::spy();

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_refund_failure']))
            ->assertRedirect(route('my.bookings'))
            ->assertSessionHas('status', 'We could not complete the booking or refund your payment. Please contact support.');

        $this->get(route('my.bookings'))
            ->assertOk()
            ->assertSee('Please contact support.')
            ->assertDontSee('Sensitive Stripe detail');
        $this->assertDatabaseHas('payments', [
            'stripe_payment_intent_id' => 'pi_refund_failure',
            'status' => 'failed',
            'receipt_number' => null,
            'paid_at' => null,
        ]);
        Log::shouldHaveReceived('critical')->once()->with(
            'Stripe refund failed after booking finalization failure.',
            Mockery::on(fn (array $context): bool => $context['booking_id'] === $booking->id
                && $context['stripe_payment_intent_id'] === 'pi_refund_failure'
                && $context['exception_class'] !== ''),
        );
    }

    public function test_stripe_create_api_error_shows_friendly_message_and_records_nothing(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking();
        Log::spy();
        $this->mockStripeApiError();

        $this->actingAs($user)
            ->post(route('book.payment.intent'), ['booking_id' => $booking->id])
            ->assertRedirect(route('book.payment', ['booking_id' => $booking->id]))
            ->assertSessionHas('status', 'We could not start Stripe Checkout. Please try again.');

        $this->get(route('book.payment', ['booking_id' => $booking->id]))
            ->assertOk()
            ->assertSee('We could not start Stripe Checkout. Please try again.')
            ->assertDontSee('Sensitive Stripe detail');

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $booking->fresh()->status);
        Log::shouldHaveReceived('error')->once()->with(
            'Stripe Checkout session creation failed.',
            Mockery::on(fn (array $context): bool => ($context['exception'] ?? null) instanceof ApiErrorException
                && str_contains($context['exception']->getMessage(), 'Sensitive Stripe detail')),
        );
    }

    public function test_stripe_retrieve_api_error_shows_friendly_message_and_records_nothing(): void
    {
        [$user] = $this->makeCustomerBooking();
        Log::spy();
        $this->mockStripeApiError();

        $this->actingAs($user)
            ->get(route('book.payment.return', ['session_id' => 'cs_test_retrieve_error']))
            ->assertRedirect(route('my.bookings'))
            ->assertSessionHas('status', 'We could not verify the payment with Stripe. Please check your bookings or contact support.');

        $this->assertDatabaseCount('payments', 0);
        Log::shouldHaveReceived('error')->once()->with(
            'Stripe Checkout session retrieval failed.',
            Mockery::on(fn (array $context): bool => ($context['exception'] ?? null) instanceof ApiErrorException
                && str_contains($context['exception']->getMessage(), 'Sensitive Stripe detail')),
        );
    }

    public function test_missing_or_invalid_booking_id_shows_link_back_to_my_bookings(): void
    {
        [$user] = $this->makeCustomerBooking();

        $this->actingAs($user)
            ->get(route('book.payment'))
            ->assertOk()
            ->assertSee('We couldn’t find that booking.')
            ->assertSee(route('my.bookings'), false);

        $this->get(route('book.payment', ['booking_id' => 'not-a-number']))
            ->assertOk()
            ->assertSee('We couldn’t find that booking.')
            ->assertSee(route('my.bookings'), false);
    }

    public function test_checkout_webhook_with_a_currency_mismatch_records_nothing(): void
    {
        config()->set('services.stripe.webhook_secret', 'whsec_checkout_test');
        [, , $booking] = $this->makeCustomerBooking(total: '115.00');
        $session = $this->checkoutSessionResponse(
            'cs_test_wrong_currency',
            'https://checkout.stripe.com/c/pay/cs_test_wrong_currency',
            'paid',
            'pi_checkout_wrong_currency',
            $booking,
        );
        $session['currency'] = 'jod';

        $this->sendSignedWebhook($this->checkoutCompletedEvent($session))
            ->assertOk()
            ->assertExactJson(['received' => true]);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_checkout_has_a_clear_message_on_the_payment_page(): void
    {
        [$user, , $booking] = $this->makeCustomerBooking();

        $this->actingAs($user)
            ->get(route('book.payment', ['booking_id' => $booking->id, 'cancelled' => 1]))
            ->assertOk()
            ->assertSee('Checkout was cancelled. No payment was taken.');
    }

    private function mockBookingFinalizerToConfirm(): void
    {
        $finalizer = Mockery::mock(BookingFinalizationService::class);
        $finalizer->shouldReceive('finalize')->andReturnUsing(static function (Booking $booking): void {
            $booking->update(['status' => 'confirmed']);
        });
        app()->instance(BookingFinalizationService::class, $finalizer);
    }

    private function refundResponse(string $id, string $paymentIntentId, int $amount): array
    {
        return [
            'id' => $id,
            'object' => 'refund',
            'amount' => $amount,
            'currency' => 'usd',
            'payment_intent' => $paymentIntentId,
            'status' => 'succeeded',
        ];
    }

    private function assertRefundRequests(string $paymentIntentId, int $count): void
    {
        $requests = array_values(array_filter(
            $this->stripeRequests,
            static fn (array $request): bool => parse_url($request['url'], PHP_URL_PATH) === '/v1/refunds',
        ));

        $this->assertCount($count, $requests);

        foreach ($requests as $request) {
            $this->assertSame('post', strtolower($request['method']));
            $this->assertSame(['payment_intent' => $paymentIntentId], $request['params']);
            $this->assertContains(
                strtolower('Idempotency-Key: refund_'.$paymentIntentId),
                array_map('strtolower', $request['headers']),
            );
        }
    }

    private function mockStripeResponses(array $responses): void
    {
        config()->set('services.stripe.secret', 'sk_test_checkout_mock');
        $this->previousStripeHttpClient ??= ApiRequestor::httpClient();
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->times(count($responses))
            ->andReturnUsing(function (...$arguments) use (&$responses): array {
                $this->stripeRequests[] = [
                    'method' => $arguments[0],
                    'url' => $arguments[1],
                    'headers' => $arguments[2],
                    'params' => $arguments[3] ?? [],
                ];
                $this->stripeTransactionLevels[] = DB::transactionLevel();
                $response = array_shift($responses);
                $status = $response['_http_status'] ?? 200;
                unset($response['_http_status']);

                return [json_encode($response, JSON_THROW_ON_ERROR), $status, ['Request-Id' => 'req_checkout_test']];
            });
        ApiRequestor::setHttpClient($httpClient);

        app()->instance(PaymentService::class, new PaymentService(new StripeClient([
            'api_key' => 'sk_test_checkout_mock',
            'max_network_retries' => 0,
        ])));
    }

    private function mockStripeApiError(): void
    {
        config()->set('services.stripe.secret', 'sk_test_checkout_mock');
        $this->previousStripeHttpClient ??= ApiRequestor::httpClient();
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->andReturn([
                json_encode([
                    'error' => [
                        'type' => 'invalid_request_error',
                        'message' => 'Sensitive Stripe detail',
                    ],
                ], JSON_THROW_ON_ERROR),
                400,
                ['Request-Id' => 'req_checkout_error'],
            ]);
        ApiRequestor::setHttpClient($httpClient);

        app()->instance(PaymentService::class, new PaymentService(new StripeClient([
            'api_key' => 'sk_test_checkout_mock',
            'max_network_retries' => 0,
        ])));
    }

    private function checkoutSessionResponse(
        string $id,
        string $url,
        string $paymentStatus,
        ?string $paymentIntentId,
        Booking $booking,
    ): array {
        $paymentService = new PaymentService;

        return [
            'id' => $id,
            'object' => 'checkout.session',
            'url' => $url,
            'mode' => 'payment',
            'payment_status' => $paymentStatus,
            'payment_intent' => $paymentIntentId,
            'amount_total' => $paymentService->chargeAmountCents($booking),
            'currency' => $paymentService->currency(),
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'customer_id' => (string) $booking->customer_id,
                'amount_due_jod' => $paymentService->amountDueJod($booking),
                'amount_usd' => $paymentService->chargeAmountUsd($booking),
                'jod_to_usd_rate' => $paymentService->jodToUsdRate(),
            ],
        ];
    }

    private function checkoutCompletedEvent(array $session): array
    {
        return [
            'id' => 'evt_checkout_completed',
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'livemode' => false,
            'pending_webhooks' => 1,
            'type' => 'checkout.session.completed',
            'data' => ['object' => $session],
        ];
    }

    private function sendSignedWebhook(array $payload)
    {
        $rawPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$rawPayload, 'whsec_checkout_test');

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $rawPayload);
    }

    /** @return array{User, Customer, Booking} */
    private function makeCustomerBooking(
        string $email = 'checkout-customer@example.test',
        string $total = '100.00',
        string $creditsUsed = '0.00',
        string $status = 'pending',
    ): array {
        $user = User::create([
            'name' => 'Checkout Customer',
            'email' => $email,
            'password' => 'password',
        ]);
        $customer = Customer::create([
            'user_id' => $user->id,
            'credit_balance' => $creditsUsed,
        ]);
        $booking = Booking::create([
            'code' => sprintf('CS%010d', Booking::count() + 1),
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
}
