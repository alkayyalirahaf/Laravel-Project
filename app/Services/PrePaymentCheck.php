<?php

namespace App\Services;

/**
 * Dev 4 integration contract only; no implementation is registered.
 * Return ['available' => bool, 'warnings' => array]. Advisory checks only.
 * Dev 4/5 must independently verify ownership, quote, seats, identity and payment.
 * An AI result never authorizes a booking, balance mutation or payment.
 */
interface PrePaymentCheck
{
    public function check(int $bookingId, int $customerId): array;
}
