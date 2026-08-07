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
    /**
     * Codes that genuinely mean "this token can no longer be used":
     * 190 = access token expired / revoked / invalid, 102 = session invalid.
     *
     * Deliberately narrow. Meta stamps `type: OAuthException` on many errors
     * that have nothing to do with the token — notably code 100 ("Invalid
     * parameter") from the Send API and code 200/10 (missing permission) —
     * so keying off the *type* would disable a healthy connection on the
     * first bad request. Key off these codes only.
     */
    private const TOKEN_INVALID_CODES = [190, 102];

    public function __construct(
        string $message,
        public readonly int $metaCode,
        public readonly string $metaType,
        public readonly string $fbtraceId,
        int $httpStatus = 0,
        public readonly ?int $metaSubcode = null,
    ) {
        parent::__construct($message, $httpStatus);
    }

    /**
     * Whether this error indicates an expired or revoked token.
     */
    public function isAuthError(): bool
    {
        return in_array($this->metaCode, self::TOKEN_INVALID_CODES, true);
    }
}
