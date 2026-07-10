<?php

namespace App\Services\Meta;

use App\Data\Meta\IncomingWebhookEvent;
use App\Enums\ChannelPlatform;
use App\Enums\WebhookSurface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Normalizes a raw Meta webhook payload into a collection of actionable
 * {@see IncomingWebhookEvent}s, routing by `object` + `field`. Anything that
 * isn't a supported surface (post comment / story reply / story mention) is
 * dropped so the pipeline only ever sees events it can act on.
 */
class MetaWebhookParser
{
    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, IncomingWebhookEvent>
     */
    public function parse(array $payload): Collection
    {
        $object = (string) Arr::get($payload, 'object');
        $platform = $object === 'instagram' ? ChannelPlatform::Instagram : ChannelPlatform::Facebook;

        /** @var Collection<int, IncomingWebhookEvent> $events */
        $events = collect();

        foreach (Arr::get($payload, 'entry', []) as $entry) {
            $assetId = (string) Arr::get($entry, 'id');

            foreach (Arr::get($entry, 'changes', []) as $change) {
                $event = $this->parseChange($platform, $assetId, $change);
                if ($event !== null) {
                    $events->push($event);
                }
            }

            foreach (Arr::get($entry, 'messaging', []) as $messaging) {
                $event = $this->parseMessaging($platform, $assetId, $messaging);
                if ($event !== null) {
                    $events->push($event);
                }
            }
        }

        return $events;
    }

    /**
     * Feed/comment changes (FB page `feed`, IG `comments`).
     *
     * @param  array<string, mixed>  $change
     */
    protected function parseChange(ChannelPlatform $platform, string $assetId, array $change): ?IncomingWebhookEvent
    {
        $field = (string) Arr::get($change, 'field');
        $value = (array) Arr::get($change, 'value', []);

        // Facebook page feed → only comment additions.
        if ($field === 'feed') {
            if (Arr::get($value, 'item') !== 'comment') {
                return null;
            }

            return new IncomingWebhookEvent(
                platform: $platform,
                surface: WebhookSurface::PostComment,
                assetId: $assetId,
                objectId: (string) Arr::get($value, 'comment_id'),
                actorId: Arr::get($value, 'from.id') !== null ? (string) Arr::get($value, 'from.id') : null,
                text: Arr::get($value, 'message'),
                parentId: Arr::get($value, 'post_id'),
                verb: Arr::get($value, 'verb'),
                raw: $value,
            );
        }

        // Instagram comments.
        if ($field === 'comments') {
            return new IncomingWebhookEvent(
                platform: $platform,
                surface: WebhookSurface::PostComment,
                assetId: $assetId,
                objectId: (string) Arr::get($value, 'id'),
                actorId: Arr::get($value, 'from.id') !== null ? (string) Arr::get($value, 'from.id') : null,
                text: Arr::get($value, 'text'),
                parentId: Arr::get($value, 'media.id'),
                raw: $value,
            );
        }

        return null;
    }

    /**
     * Messaging events carrying a story-reply or story-mention context.
     *
     * @param  array<string, mixed>  $messaging
     */
    protected function parseMessaging(ChannelPlatform $platform, string $assetId, array $messaging): ?IncomingWebhookEvent
    {
        $message = (array) Arr::get($messaging, 'message', []);
        $senderId = Arr::get($messaging, 'sender.id') !== null ? (string) Arr::get($messaging, 'sender.id') : null;
        $messageId = (string) Arr::get($message, 'mid');

        // Story reply: the user replied to the account's story.
        if (Arr::get($message, 'reply_to.story') !== null) {
            return new IncomingWebhookEvent(
                platform: $platform,
                surface: WebhookSurface::StoryReply,
                assetId: $assetId,
                objectId: $messageId,
                actorId: $senderId,
                text: Arr::get($message, 'text'),
                parentId: Arr::get($message, 'reply_to.story.id'),
                raw: $messaging,
            );
        }

        // Story mention: the user @mentioned the account in their story.
        foreach ((array) Arr::get($message, 'attachments', []) as $attachment) {
            if (Arr::get($attachment, 'type') === 'story_mention') {
                return new IncomingWebhookEvent(
                    platform: $platform,
                    surface: WebhookSurface::StoryMention,
                    assetId: $assetId,
                    objectId: $messageId,
                    actorId: $senderId,
                    text: Arr::get($message, 'text'),
                    parentId: Arr::get($attachment, 'payload.url'),
                    raw: $messaging,
                );
            }
        }

        return null;
    }
}
