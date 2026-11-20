<?php

namespace App\Services;

use App\Mail\BookingTickets;
use App\Models\Booking;
use App\Models\Seat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingFinalizationService
{
    public static function holdKey(Booking $booking): string
    {
        return 'booking-seat-hold:'.$booking->code;
    }

    public function finalize(Booking $booking): void
    {
        DB::transaction(function () use ($booking): void {
            $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($booking->status !== 'pending') {
                return;
            }

            $payment = $booking->payments()->where('status', 'succeeded')->first();
            $due = app(PaymentService::class)->amountDueJod($booking);
            if (($payment?->amount ?? '0.00') !== $due) {
                return;
            }

            $hold = Cache::store('database')->get(self::holdKey($booking));
            $expires = $hold['expires'] ?? null;
            $hold = $hold['seats'] ?? null;
            $legs = $booking->bookingFlights()->with('flight.airline')->get();
            $passengers = $booking->passengers()->orderBy('id')->get();
            $seatIds = collect($hold ?? [])->flatten();
            $seats = Seat::whereKey($seatIds->all())->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if (!$hold || $seatIds->unique()->count() !== $legs->count() * $passengers->count()
                || $legs->contains(fn ($leg) => $leg->flight->airline->status !== 'active'
                    || !in_array($leg->flight->status, ['scheduled', 'delayed'], true)
                    || !$leg->flight->departure_at->isFuture())
                || $seats->count() !== $seatIds->count()
                || $seats->contains(fn ($seat) => $seat->status !== 'held'
                    || (int) $seat->held_by !== (int) $booking->customer_id
                    || !$seat->held_until?->isFuture() || $seat->held_until->timestamp !== $expires)) {
                Log::warning('Payment recorded but booking requires manual reconciliation.', ['booking_id' => $booking->id]);

                return;
            }

            foreach ($legs as $leg) {
                $legSeats = collect($hold[$leg->direction] ?? [])->map(fn ($id) => $seats->get($id));
                if ($legSeats->count() !== $passengers->count() || $legSeats->contains(
                    fn ($seat) => !$seat || (int) $seat->flight_class_id !== (int) $leg->flight_class_id
                )) {
                    Log::warning('Booking requires seat allocation review.', ['booking_id' => $booking->id]);

                    return;
                }
            }

            foreach ($legs as $leg) {
                foreach ($passengers as $index => $passenger) {
                    $seat = $seats->get($hold[$leg->direction][$index]);
                    $number = strtoupper(Str::random(20));
                    $leg->tickets()->create([
                        'passenger_id' => $passenger->id, 'seat_id' => $seat->id,
                        'ticket_number' => $number, 'qr_code' => $number, 'status' => 'issued',
                    ]);
                    $seat->update(['status' => 'booked', 'held_until' => null, 'held_by' => null]);
                }
            }

            $booking->update(['status' => 'confirmed', 'amount_paid' => $payment?->amount ?? '0.00']);
            Cache::store('database')->forget(self::holdKey($booking));
            $notice = $booking->notificationLogs()->create([
                'channel' => 'email', 'type' => 'confirmation', 'scheduled_at' => now(), 'status' => 'pending',
            ]);

            DB::afterCommit(function () use ($booking, $notice): void {
                try {
                    Mail::to($booking->contact_email)->send(new BookingTickets($booking));
                    $notice->update(['status' => 'sent', 'sent_at' => now()]);
                } catch (\Throwable $exception) {
                    $notice->update(['status' => 'failed']);
                    report($exception);
                }
            });
        }, 3);
    }
}
