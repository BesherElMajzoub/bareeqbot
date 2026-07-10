<?php

namespace App\Data\Meta;

use Spatie\LaravelData\Data;

/**
 * Represents a Facebook Page (and its optionally linked Instagram business account)
 * as returned by GET /me/accounts.
 */
class MetaPageData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $access_token,
        /** @var string[]|null */
        public readonly ?array $tasks,
        /** IG business account id linked to this page (if any). */
        public readonly ?string $instagram_business_account_id,
        /** IG business account username linked to this page (if any). */
        public readonly ?string $instagram_username,
    ) {}
}
