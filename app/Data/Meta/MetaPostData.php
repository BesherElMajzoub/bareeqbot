<?php

namespace App\Data\Meta;

use Spatie\LaravelData\Data;

/**
 * A Facebook Page post or Instagram media item, as returned by GET
 * /{page-id}/posts or /{ig-id}/media, trimmed down for the rules UI's
 * "pick a specific post" dropdown.
 */
class MetaPostData extends Data
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly ?string $created_time,
    ) {}
}
