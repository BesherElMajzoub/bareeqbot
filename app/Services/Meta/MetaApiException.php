<?php

namespace App\Services\Meta;

use RuntimeException;

/**
 * Wraps a structured error returned by the Meta Graph API.
 *
 * @see https://developers.facebook.com/docs/graph-api/guides/error-handling/
 */
class MetaApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $metaCode,
        public readonly string $metaType,
        public readonly string $fbtraceId,
        int $httpStatus = 0,
    ) {
        parent::__construct($message, $httpStatus);
    }

    /**
     * Whether this error indicates an expired or revoked token (OAuthException code 190).
     */
    public function isAuthError(): bool
    {
        return $this->metaType === 'OAuthException' || $this->metaCode === 190;
    }
}
