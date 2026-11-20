<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CreditTransaction;

class CreditService
{
    public const BLOCKED = 'Credit changes are blocked: credit_transactions requires a unique operation_id and a responsible administrator field. No balance or ledger entry was changed.';

    public function getBalance(int $customerId): string
    {
        return Customer::findOrFail($customerId)->credit_balance;
    }

    // Contracts for Dev 4 and Dev 5. Fail closed until the schema owner adds
    // operation_id UNIQUE and actor_user_id, and the model owner exposes them.
    public function addCredits(int $customerId, string $amount, string $operationId, string $reason, ?int $actorUserId = null, ?int $bookingId = null): CreditTransaction
    {
        throw new \LogicException(self::BLOCKED);
    }

    public function deductCredits(int $customerId, string $amount, string $operationId, string $reason, ?int $actorUserId = null, ?int $bookingId = null): CreditTransaction
    {
        throw new \LogicException(self::BLOCKED);
    }

    public function adjustBalance(int $customerId, string $targetBalance, string $operationId, string $reason, int $actorUserId): CreditTransaction
    {
        throw new \LogicException(self::BLOCKED);
    }
}
