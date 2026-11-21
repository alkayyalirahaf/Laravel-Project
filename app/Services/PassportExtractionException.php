<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class PassportExtractionException extends RuntimeException
{
    public const UNREADABLE = 'unreadable';

    public const UNAVAILABLE = 'unavailable';

    public function __construct(
        public readonly string $reason,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }
}
