<?php

namespace App\Data\Meta;

use App\Enums\ChannelPlatform;
use App\Enums\WebhookSurface;
use Spatie\LaravelData\Data;

/**
 * A single actionable webhook event, normalized from Meta's raw payload by the
 * MetaWebhookParser. This is the currency the rule engine (Phase 5) consumes.
 */
class IncomingWebhookEvent extends Data
{
    public function __construct(
        public readonly ChannelPlatform $platform,
        public readonly WebhookSurface $surface,
        /** Page id or IG user id — used to resolve the ChannelConnection + tenant. */
        public readonly string $assetId,
        /** Comment/message id — the object we would reply to (idempotency source). */
        public readonly string $objectId,
        /** Commenter / sender id — used to skip self-authored events. */
        public readonly ?string $actorId = null,
        /** Comment / message text — for rule matching. */
        public readonly ?string $text = null,
        /** Parent comment id or media/story id. */
        public readonly ?string $parentId = null,
        /** add | edit | remove (for feed changes). */
        public readonly ?string $verb = null,
        /** @var array<string, mixed> */
        public readonly array $raw = [],
    ) {}
}
