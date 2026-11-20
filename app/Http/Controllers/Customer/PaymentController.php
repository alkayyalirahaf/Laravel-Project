<?php

namespace App\Http\Controllers\Customer;

use App\Models\Booking;
use App\Models\Customer;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PaymentController
{
    public function show(Request $request, PaymentService $paymentService): View
    {
        $bookingId = $this->validBookingId($request->query('booking_id'));
        if ($bookingId === null) {
            return $this->invalidBookingView();
        }

        $customer = $this->customer($request);
        $booking = $this->findOwnedBooking($bookingId, $customer);
        if ($booking === null) {
            abort_if(Booking::query()->whereKey($bookingId)->exists(), 404);

            return $this->invalidBookingView();
        }

        abort_unless($booking->status === 'pending', 409, 'This booking is not awaiting payment.');

        return view('booking.payment', [
            'title' => 'Payment',
            'booking' => $booking,
            'amountDueJod' => $paymentService->amountDueJod($booking),
            'amountDueUsd' => $paymentService->chargeAmountUsd($booking),
            'stripeCurrency' => $paymentService->currency(),
            'jodToUsdRate' => $paymentService->jodToUsdRate(),
            'cancelled' => $request->boolean('cancelled'),
        ]);
    }

    /** Retain the existing route action while it hands off to hosted Checkout. */
    public function intent(Request $request, PaymentService $paymentService): RedirectResponse
    {
        return $this->checkout($request, $paymentService);
    }

    public function checkout(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $bookingId = $this->validBookingId($request->input('booking_id'));
        if ($bookingId === null) {
            return redirect()->route('book.payment');
        }

        $customer = $this->customer($request);
        $booking = $this->findOwnedBooking($bookingId, $customer);
        if ($booking === null) {
            abort_if(Booking::query()->whereKey($bookingId)->exists(), 404);

            return redirect()->route('book.payment');
        }

        abort_unless($booking->status === 'pending', 409, 'This booking is not awaiting payment.');

        try {
            $session = $paymentService->createCheckoutSession($booking, $customer);
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() !== 503) {
                throw $exception;
            }

            return redirect()->route('book.payment', ['booking_id' => $booking->getKey()])
                ->with('status', 'Stripe Checkout is currently unavailable. Your booking is still pending.');
        } catch (ApiErrorException $exception) {
            Log::error('Stripe Checkout session creation failed.', [
                'booking_id' => $booking->getKey(),
                'exception' => $exception,
            ]);

            return redirect()->route('book.payment', ['booking_id' => $booking->getKey()])
                ->with('status', 'We could not start Stripe Checkout. Please try again.');
        }

        if ($session === null) {
            return redirect()->route('book.confirmation', ['booking_id' => $booking->getKey()])
                ->with('status', 'Credits cover this booking. Your booking is being finalized.');
        }

        abort_unless(is_string($session->url ?? null), 502, 'Stripe did not return a Checkout URL.');

        return redirect()->away($session->url);
    }

    public function return(Request $request, PaymentService $paymentService): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:255'],
        ]);
        $customer = $this->customer($request);
        try {
            $session = $paymentService->retrieveCheckoutSession($validated['session_id']);
        } catch (ApiErrorException $exception) {
            Log::error('Stripe Checkout session retrieval failed.', [
                'session_id' => $validated['session_id'],
                'exception' => $exception,
            ]);

            return redirect()->route('my.bookings')
                ->with('status', 'We could not verify the payment with Stripe. Please check your bookings or contact support.');
        }
        $bookingId = filter_var($session->metadata->booking_id ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $sessionCustomerId = filter_var($session->metadata->customer_id ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        abort_unless($bookingId !== false && $sessionCustomerId === (int) $customer->getKey(), 404);

        $booking = $this->ownedBooking((int) $bookingId, $customer);
        if (($session->payment_status ?? null) !== 'paid') {
            return redirect()->route('book.payment', ['booking_id' => $booking->getKey()])
                ->with('status', 'Payment was not completed. No payment was recorded; you can try again.');
        }

        $amountJod = $paymentService->verifiedCheckoutJodAmount(
            $session->metadata ?? null,
            $session->amount_total ?? null,
            $session->currency ?? null,
        );

        if ($amountJod === null) {
            return redirect()->route('book.payment', ['booking_id' => $booking->getKey()])
                ->with('status', 'The Stripe charge could not be verified. No payment was recorded; contact support.');
        }

        $paymentIntent = $session->payment_intent ?? null;
        $paymentIntentId = is_string($paymentIntent)
            ? $paymentIntent
            : (is_object($paymentIntent) ? ($paymentIntent->id ?? null) : null);

        if (! is_string($paymentIntentId) || $paymentIntentId === '') {
            return redirect()->route('book.payment', ['booking_id' => $booking->getKey()])
                ->with('status', 'Stripe could not verify the payment. No payment was recorded.');
        }

        $paymentService->markPaid(
            $paymentIntentId,
            (int) $booking->getKey(),
            (int) $customer->getKey(),
            $amountJod,
        );

        return redirect()->route('book.confirmation', ['booking_id' => $booking->getKey()])
            ->with('status', 'Payment confirmed. Your booking is being finalized.');
    }

    private function customer(Request $request): Customer
    {
        $customer = $request->user()?->customer;
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    private function ownedBooking(int $bookingId, Customer $customer): Booking
    {
        return $this->findOwnedBooking($bookingId, $customer) ?? abort(404);
    }

    private function findOwnedBooking(int $bookingId, Customer $customer): ?Booking
    {
        return Booking::query()
            ->where('customer_id', $customer->getKey())
            ->whereKey($bookingId)
            ->first();
    }

    private function validBookingId(mixed $value): ?int
    {
        $bookingId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $bookingId === false ? null : (int) $bookingId;
    }

    private function invalidBookingView(): View
    {
        return view('booking.payment', [
            'title' => 'Payment',
            'booking' => null,
            'cancelled' => false,
        ]);
    }
}
