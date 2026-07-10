<?php

use App\Enums\ChannelPlatform;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use Illuminate\Support\Facades\Http;

test('an instagram public reply hits the replies edge', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'r1'])]);
    $connection = ChannelConnection::factory()->instagram()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    SendReply::dispatchSync(
        channelConnectionId: $connection->id,
        ruleId: $rule->id,
        platform: ChannelPlatform::Instagram,
        surface: WebhookSurface::PostComment,
        sourceObjectId: 'igc1',
        actorId: '999',
        actionType: RuleActionType::PublicReply,
        messageTemplate: 'Thanks!',
        context: [],
    );

    // Facebook uses /comments, Instagram uses /replies.
    Http::assertSent(fn ($request) => str_contains($request->url(), 'igc1/replies'));
});
