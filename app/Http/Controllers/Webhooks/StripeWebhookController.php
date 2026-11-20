<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;

class StripeWebhookController
{
    public function handle(Request $request, PaymentService $paymentService): JsonResponse
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
                (string) config('services.stripe.webhook_secret', ''),
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response()->json(['error' => 'Invalid Stripe webhook signature or payload.'], 400);
        }

        $object = $event->data->object;
        $metadataBookingId = filter_var($object->metadata->booking_id ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $metadataCustomerId = filter_var($object->metadata->customer_id ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($event->type === 'checkout.session.completed') {
            $paymentIntentId = $object->payment_intent ?? null;
            $booking = $this->bookingFromMetadata($metadataBookingId, $metadataCustomerId);
            $amountJod = $paymentService->verifiedCheckoutJodAmount(
                $object->metadata ?? null,
                $object->amount_total ?? null,
                $object->currency ?? null,
            );

            if (($object->payment_status ?? null) === 'paid'
                && is_string($paymentIntentId)
                && $booking !== null
                && $amountJod !== null) {
                $paymentService->markPaid(
                    $paymentIntentId,
                    (int) $metadataBookingId,
                    (int) $metadataCustomerId,
                    $amountJod,
                );
            }

            return response()->json(['received' => true]);
        }

        $paymentIntentId = $object->id ?? null;

        if (! is_string($paymentIntentId) || $paymentIntentId === '') {
            return response()->json(['received' => true]);
        }

        $booking = $this->bookingFromMetadata($metadataBookingId, $metadataCustomerId);
        $amountJod = $paymentService->verifiedCheckoutJodAmount(
            $object->metadata ?? null,
            $object->amount ?? null,
            $object->currency ?? null,
        );
        $validCharge = $booking !== null && $amountJod !== null;

        match ($event->type) {
            'payment_intent.succeeded' => $validCharge ? $paymentService->markPaid(
                $paymentIntentId,
                (int) $metadataBookingId,
                (int) $metadataCustomerId,
                $amountJod,
            ) : false,
            'payment_intent.payment_failed' => $validCharge ? $paymentService->markFailed(
                $paymentIntentId,
                (int) $metadataBookingId,
                (int) $metadataCustomerId,
                $amountJod,
            ) : false,
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function bookingFromMetadata(int|false $bookingId, int|false $customerId): ?Booking
    {
        if ($bookingId === false || $customerId === false) {
            return null;
        }

        return Booking::query()
            ->whereKey($bookingId)
            ->where('customer_id', $customerId)
            ->first();
    }
}
