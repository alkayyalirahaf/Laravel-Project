<?php

namespace App\Services;

/**
 * Dev 4 integration contract only; no provider implementation is registered.
 * Dev 4 must authorize ownership of the private file and obtain user consent.
 * Return ['available' => bool, 'fields' => array, 'warnings' => array].
 * Extracted fields are suggestions: the customer must confirm them.
 * Never log document contents or send them through the travel-tips gateway.
 */
interface PassportExtraction
{
    public function extract(string $privateFilePath, int $customerId): array;
}
