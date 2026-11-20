<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function applyToBooking(Booking $booking, Customer $customer): void
    {
        DB::transaction(function () use ($booking, $customer) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $booking->customer_id === (int) $customer->id, 403);
            abort_unless($booking->status === 'pending', 409);
            $amount = $this->cents($booking->credits_used);
            if ($amount === 0) {
                return;
            }
            if ($amount < 0 || $amount > $this->cents($booking->total)) {
                throw ValidationException::withMessages(['credits' => 'Credit usage must not exceed the booking total.']);
            }
            $operation = (string) \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL, 'yalla-teer/booking-credit/'.$booking->id);
            $this->deductCredits($customer->id, $this->money($amount), $operation, 'Booking credit payment', null, $booking->id);
        }, 3);
    }


    public function getBalance(int $customerId): string
    {
        return Customer::findOrFail($customerId)->credit_balance;
    }

    public function addCredits(int $customerId, string $amount, string $operationId, string $reason, ?int $actorUserId = null, ?int $bookingId = null): CreditTransaction
    {
        return $this->mutate('add', $customerId, $amount, $operationId, $reason, $actorUserId, $bookingId);
    }

    public function deductCredits(int $customerId, string $amount, string $operationId, string $reason, ?int $actorUserId = null, ?int $bookingId = null): CreditTransaction
    {
        return $this->mutate('deduct', $customerId, $amount, $operationId, $reason, $actorUserId, $bookingId);
    }

    public function adjustBalance(int $customerId, string $targetBalance, string $operationId, string $reason, int $actorUserId): CreditTransaction
    {
        return $this->mutate('adjust', $customerId, $targetBalance, $operationId, $reason, $actorUserId, null);
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function money(int $cents): string
    {
        return ($cents < 0 ? '-' : '').intdiv(abs($cents), 100).'.'.str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    private function mutate(string $action, int $customerId, string $amount, string $operationId, string $reason, ?int $actorUserId, ?int $bookingId): CreditTransaction
    {
        Validator::make(['amount' => $amount, 'operation_id' => $operationId, 'reason' => $reason], [
            'amount' => ['required', 'numeric', 'regex:/^\d{1,8}(\.\d{1,2})?$/', $action === 'adjust' ? 'min:0' : 'gt:0', 'max:99999999.99'],
            'operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:200'],
        ])->validate();

        return DB::transaction(function () use ($action, $customerId, $amount, $operationId, $reason, $actorUserId, $bookingId) {
            $customer = Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();
            if ($actorUserId !== null) {
                abort_unless(User::findOrFail($actorUserId)->superAdmin, 403);
            } elseif ($action === 'adjust' || $bookingId === null) {
                throw ValidationException::withMessages(['amount' => 'A responsible administrator or booking is required.']);
            }
            if ($bookingId !== null) {
                abort_unless(Booking::whereKey($bookingId)->where('customer_id', $customerId)->exists(), 403);
            }
            $requested = $this->money($this->cents($amount));
            $existing = CreditTransaction::where('operation_id', $operationId)->first();
            if ($existing) {
                if ((int) $existing->customer_id !== $customerId || $existing->operation_action !== $action
                    || (string) $existing->requested_amount !== $requested || $existing->note !== $reason
                    || $existing->actor_user_id !== $actorUserId || $existing->booking_id !== $bookingId) {
                    throw ValidationException::withMessages(['operation_id' => 'This operation key has already been used for a different request.']);
                }
                return $existing;
            }
            $before = $this->cents($customer->credit_balance);
            $value = $this->cents($amount);
            $after = match ($action) { 'add' => $before + $value, 'deduct' => $before - $value, 'adjust' => $value };
            if ($after < 0 || $after > 9999999999) {
                throw ValidationException::withMessages(['amount' => 'Insufficient credit or balance exceeds the supported limit.']);
            }
            $customer->update(['credit_balance' => $this->money($after)]);
            return CreditTransaction::create([
                'customer_id' => $customerId, 'booking_id' => $bookingId, 'actor_user_id' => $actorUserId,
                'operation_id' => $operationId, 'operation_action' => $action, 'requested_amount' => $requested,
                'type' => $actorUserId !== null ? 'admin_adjust' : ($action === 'deduct' ? 'used' : 'cancel_credit'),
                'amount' => $this->money($after - $before), 'balance_after' => $this->money($after), 'note' => $reason,
            ]);
        }, 3);
    }
}
