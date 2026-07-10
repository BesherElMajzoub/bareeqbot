<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a tenant tries to connect more channels than their plan allows.
 * The handler should translate this to a 422 response with a user-visible message.
 */
class QuotaExceededException extends RuntimeException
{
    public function __construct(string $message = 'Quota exceeded: cannot connect more channels on the current plan.')
    {
        parent::__construct($message);
    }
}
