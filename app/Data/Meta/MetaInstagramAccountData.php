<?php

namespace App\Data\Meta;

use Spatie\LaravelData\Data;

/**
 * Represents a Meta Instagram business account as returned from
 * the instagram_business_account field on a page from /me/accounts.
 */
class MetaInstagramAccountData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $username,
        public readonly ?string $name,
    ) {}
}
