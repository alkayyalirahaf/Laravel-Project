<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\StripeClient;

class PaymentService
{
    public function __construct(private readonly ?StripeClient $stripeClient = null) {}

    /** Return the payable booking amount as integer JOD piastres. */
    public function amountDueJodCents(Booking $booking): int
    {
        $totalCents = $this->toCents((string) $booking->getRawOriginal('total'));
        $creditsCents = $this->toCents((string) $booking->getRawOriginal('credits_used'));

        return max(0, $totalCents - $creditsCents);
    }

    public function amountDueJod(Booking $booking): string
    {
        return $this->formatCents($this->amountDueJodCents($booking));
    }

    public function chargeAmountCents(Booking $booking): int
    {
        return (int) round(
            $this->amountDueJodCents($booking) * (float) $this->jodToUsdRate(),
            0,
            PHP_ROUND_HALF_UP,
        );
    }

    public function chargeAmountUsd(Booking $booking): string
    {
        return $this->formatCents($this->chargeAmountCents($booking));
    }

    public function currency(): string
    {
        return 'usd';
    }

    public function jodToUsdRate(): string
    {
        $rate = config('services.stripe.jod_rate', 1.41044);

        if (! is_numeric($rate) || (float) $rate <= 0) {
            abort(503, 'The JOD to Stripe currency rate is not configured correctly.');
        }

        return rtrim(rtrim(sprintf('%.8F', (float) $rate), '0'), '.');
    }

    /** Validate a Checkout charge against its immutable Stripe metadata snapshot. */
    public function verifiedCheckoutJodAmount(mixed $metadata, mixed $amount, mixed $currency): ?string
    {
        $metadataValue = static function (mixed $metadata, string $key): mixed {
            if (is_array($metadata)) {
                return $metadata[$key] ?? null;
            }

            return is_object($metadata) ? ($metadata->{$key} ?? null) : null;
        };

        $jod = $metadataValue($metadata, 'amount_due_jod');
        $usd = $metadataValue($metadata, 'amount_usd');
        $rate = $metadataValue($metadata, 'jod_to_usd_rate');

        if (! is_string($jod) || ! preg_match('/^\d+\.\d{2}$/', $jod)
            || ! is_string($usd) || ! preg_match('/^\d+\.\d{2}$/', $usd)
            || ! is_string($rate) || ! preg_match('/^\d+(?:\.\d{1,8})?$/', $rate)
            || ! is_numeric($rate) || ! is_finite((float) $rate) || (float) $rate <= 0
            || ! is_string($currency) || strtolower($currency) !== 'usd') {
            return null;
        }

        $chargedCents = is_int($amount) || (is_string($amount) && ctype_digit($amount))
            ? (int) $amount
            : null;
        $jodCents = $this->toCents($jod);
        $usdCents = $this->toCents($usd);
        $convertedCents = (int) round($jodCents * (float) $rate, 0, PHP_ROUND_HALF_UP);

        if ($jodCents <= 0 || $chargedCents === null
            || $usdCents !== $convertedCents || $chargedCents !== $usdCents) {
            return null;
        }

        return $this->formatCents($jodCents);
    }

    private function formatCents(int $amountCents): string
    {
        return intdiv($amountCents, 100).'.'.str_pad((string) ($amountCents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Create a hosted Stripe Checkout session. Null means credits cover the booking. */
    public function createCheckoutSession(Booking $booking, Customer $customer): ?CheckoutSession
    {
        $checkoutRequest = DB::transaction(function () use ($booking, $customer): ?array {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());
            abort_unless((int) $booking->customer_id === (int) $customer->getKey(), 404);
            abort_unless($booking->status === 'pending', 409, 'This booking is not awaiting payment.');

            $booking = $this->applyCreditsHook($booking, $customer);
            abort_unless((int) $booking->customer_id === (int) $customer->getKey(), 404);
            abort_unless($booking->status === 'pending', 409, 'This booking is not awaiting payment.');
            $amountDueJodCents = $this->amountDueJodCents($booking);
            $amountDueJod = $this->amountDueJod($booking);
            $amountUsd = $this->chargeAmountUsd($booking);
            $chargeAmountCents = $this->chargeAmountCents($booking);
            $currency = 'usd';

            if ($amountDueJodCents === 0) {
                $this->finalizeBookingHook($booking);

                return null;
            }

            abort_unless($chargeAmountCents > 0, 422, 'The converted payment amount must be greater than zero.');

            $successUrl = route('book.payment.return').'?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('book.payment', [
                'booking_id' => $booking->getKey(),
                'cancelled' => 1,
            ]);
            $metadata = [
                'booking_id' => (string) $booking->getKey(),
                'customer_id' => (string) $customer->getKey(),
                'amount_due_jod' => $amountDueJod,
                'amount_usd' => $amountUsd,
                'jod_to_usd_rate' => $this->jodToUsdRate(),
            ];

            return [
                'params' => [
                    'mode' => 'payment',
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => $metadata,
                    'payment_intent_data' => ['metadata' => $metadata],
                    'line_items' => [[
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => 'usd',
                            'unit_amount' => $chargeAmountCents,
                            'product_data' => ['name' => 'Yalla Teer booking '.$booking->code],
                        ],
                    ]],
                ],
                'options' => [
                    'idempotency_key' => 'booking-'.$booking->getKey().'-checkout-'.$currency.'-'.$chargeAmountCents,
                ],
            ];
        });

        if ($checkoutRequest === null) {
            return null;
        }

        return $this->stripe()->checkout->sessions->create(
            $checkoutRequest['params'],
            $checkoutRequest['options'],
        );
    }

    public function retrieveCheckoutSession(string $sessionId): CheckoutSession
    {
        return $this->stripe()->checkout->sessions->retrieve($sessionId);
    }

    /** Mark a Stripe payment successful. Repeated returns/webhooks are no-ops. */
    public function markPaid(
        string $paymentIntentId,
        ?int $bookingId = null,
        ?int $customerId = null,
        ?string $amountJod = null,
    ): bool {
        return DB::transaction(function () use ($paymentIntentId, $bookingId, $customerId, $amountJod): bool {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            $resolvedBookingId = $payment?->booking_id ?? $bookingId;

            if ($resolvedBookingId === null) {
                return false;
            }

            $bookingQuery = Booking::query()->whereKey($resolvedBookingId)->lockForUpdate();

            if ($customerId !== null) {
                $bookingQuery->where('customer_id', $customerId);
            }

            $booking = $bookingQuery->first();

            if ($booking === null) {
                return false;
            }

            if (($bookingId !== null && (int) $booking->getKey() !== $bookingId)
                || ($customerId !== null && (int) $booking->customer_id !== $customerId)) {
                return false;
            }

            $otherSucceededPayment = Payment::query()
                ->where('booking_id', $booking->getKey())
                ->whereIn('status', ['paid', 'succeeded'])
                ->where(function ($query) use ($paymentIntentId): void {
                    $query->whereNull('stripe_payment_intent_id')
                        ->orWhere('stripe_payment_intent_id', '!=', $paymentIntentId);
                })
                ->lockForUpdate()
                ->first();

            if ($otherSucceededPayment !== null) {
                Log::warning('Ignoring a second successful Stripe payment for a booking.', [
                    'booking_id' => $booking->getKey(),
                    'existing_payment_id' => $otherSucceededPayment->getKey(),
                    'stripe_payment_intent_id' => $paymentIntentId,
                ]);

                return false;
            }

            if ($booking->status !== 'pending') {
                return false;
            }

            if ($payment === null) {
                // Recheck the intent after locking the booking so concurrent duplicate events create one row.
                $payment = Payment::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->first();

                if ($payment !== null && (int) $payment->booking_id !== (int) $booking->getKey()) {
                    return false;
                }

                $payment ??= $booking->payments()->create([
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'amount' => $amountJod ?? $this->amountDueJod($booking),
                    'status' => 'pending',
                ]);
            }

            if (in_array($payment->status, ['paid', 'succeeded'], true)) {
                return false;
            }

            // The existing payments.status enum uses "succeeded" (not "paid").
            $payment->amount = $amountJod ?? $this->amountDueJod($booking);
            $payment->status = 'succeeded';
            $payment->paid_at = now();
            $payment->receipt_number ??= 'YT-'.strtoupper(bin2hex(random_bytes(12)));
            $payment->save();

            $this->finalizeBookingHook($booking);

            return true;
        });
    }

    /** Mark a failed intent without ever downgrading an already successful payment. */
    public function markFailed(
        string $paymentIntentId,
        ?int $bookingId = null,
        ?int $customerId = null,
        ?string $amountJod = null,
    ): bool {
        return DB::transaction(function () use ($paymentIntentId, $bookingId, $customerId, $amountJod): bool {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            $booking = null;

            if ($payment === null && $bookingId !== null) {
                $bookingQuery = Booking::query()->whereKey($bookingId)->lockForUpdate();

                if ($customerId !== null) {
                    $bookingQuery->where('customer_id', $customerId);
                }

                $booking = $bookingQuery->first();

                if ($booking === null) {
                    return false;
                }

                $payment = Payment::query()
                    ->where('stripe_payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()
                    ->first();

                $payment ??= $booking->payments()->create([
                    'stripe_payment_intent_id' => $paymentIntentId,
                    'amount' => $amountJod ?? $this->amountDueJod($booking),
                    'status' => 'failed',
                ]);
            }

            if ($payment === null) {
                return false;
            }

            $booking ??= $payment->booking()->lockForUpdate()->firstOrFail();

            if (($bookingId !== null && (int) $booking->getKey() !== $bookingId)
                || ($customerId !== null && (int) $booking->customer_id !== $customerId)
                || in_array($payment->status, ['paid', 'succeeded', 'failed'], true)) {
                return false;
            }

            $payment->status = 'failed';
            $payment->save();

            return true;
        });
    }

    private function stripe(): StripeClient
    {
        if ($this->stripeClient !== null && $this->stripeClient->getApiKey()) {
            return $this->stripeClient;
        }

        $secret = (string) config('services.stripe.secret');
        abort_if($secret === '', 503, 'Stripe is not configured.');

        return new StripeClient($secret);
    }

    private function toCents(string $amount): int
    {
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $amount, $parts)) {
            throw new \InvalidArgumentException('Booking amounts must be decimal JOD values.');
        }

        $cents = ((int) $parts[2] * 100) + (int) str_pad($parts[3] ?? '', 2, '0');

        return ($parts[1] ?? '') === '-' ? -$cents : $cents;
    }

    private function applyCreditsHook(Booking $booking, Customer $customer): Booking
    {
        // TODO(Dev 3): connect CreditService::applyToBooking($booking, $customer) here.
        $class = 'App\\Services\\CreditService';

        if (class_exists($class)) {
            $service = app($class);

            if (method_exists($service, 'applyToBooking')) {
                $service->applyToBooking($booking, $customer);

                return $booking->fresh();
            }
        }

        return $booking;
    }

    private function finalizeBookingHook(Booking $booking): void
    {
        // TODO(Dev 4): connect BookingFinalizationService::finalize($booking) here.
        $class = 'App\\Services\\BookingFinalizationService';

        if (! class_exists($class)) {
            return;
        }

        $service = app($class);

        if (method_exists($service, 'finalize')) {
            $service->finalize($booking);
        }
    }
}
