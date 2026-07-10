<?php

use App\Enums\ChannelPlatform;
use App\Enums\WebhookSurface;
use App\Services\Meta\MetaWebhookParser;

beforeEach(fn () => $this->parser = new MetaWebhookParser);

test('parses a facebook feed comment', function () {
    $events = $this->parser->parse(fbCommentPayload('111', 'c1', '999', 'hi'));

    expect($events)->toHaveCount(1);
    $event = $events->first();
    expect($event->platform)->toBe(ChannelPlatform::Facebook)
        ->and($event->surface)->toBe(WebhookSurface::PostComment)
        ->and($event->assetId)->toBe('111')
        ->and($event->objectId)->toBe('c1')
        ->and($event->actorId)->toBe('999')
        ->and($event->text)->toBe('hi');
});

test('parses an instagram comment', function () {
    $events = $this->parser->parse(igCommentPayload('222', 'igc1', '888', 'love it'));

    expect($events)->toHaveCount(1);
    $event = $events->first();
    expect($event->platform)->toBe(ChannelPlatform::Instagram)
        ->and($event->surface)->toBe(WebhookSurface::PostComment)
        ->and($event->assetId)->toBe('222')
        ->and($event->objectId)->toBe('igc1')
        ->and($event->text)->toBe('love it');
});

test('parses a story reply messaging event', function () {
    $events = $this->parser->parse(storyReplyPayload('333', 'm1', '777'));

    expect($events)->toHaveCount(1);
    $event = $events->first();
    expect($event->surface)->toBe(WebhookSurface::StoryReply)
        ->and($event->assetId)->toBe('333')
        ->and($event->objectId)->toBe('m1')
        ->and($event->actorId)->toBe('777')
        ->and($event->parentId)->toBe('story-1');
});

test('parses a story mention messaging event', function () {
    $payload = [
        'object' => 'instagram',
        'entry' => [[
            'id' => '444',
            'messaging' => [[
                'sender' => ['id' => '666'],
                'recipient' => ['id' => '444'],
                'message' => [
                    'mid' => 'm2',
                    'attachments' => [[
                        'type' => 'story_mention',
                        'payload' => ['url' => 'https://example.test/story'],
                    ]],
                ],
            ]],
        ]],
    ];

    $events = $this->parser->parse($payload);

    expect($events)->toHaveCount(1)
        ->and($events->first()->surface)->toBe(WebhookSurface::StoryMention)
        ->and($events->first()->objectId)->toBe('m2');
});

test('drops non-comment feed items', function () {
    $payload = [
        'object' => 'page',
        'entry' => [[
            'id' => '111',
            'changes' => [[
                'field' => 'feed',
                'value' => ['item' => 'reaction', 'verb' => 'add'],
            ]],
        ]],
    ];

    expect($this->parser->parse($payload))->toHaveCount(0);
});

test('drops unrecognized payloads', function () {
    expect($this->parser->parse(['object' => 'page', 'entry' => []]))->toHaveCount(0);
});
