<?php

namespace App\Data\Meta;

use Spatie\LaravelData\Data;

/**
 * Short-lived or long-lived token response from Meta's OAuth endpoint.
 */
class MetaUserTokenData extends Data
{
    public function __construct(
        public readonly string $access_token,
        public readonly string $token_type,
        public readonly ?int $expires_in,
    ) {}
}
