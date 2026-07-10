<?php

use App\Enums\ChannelPlatform;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Services\Meta\MetaApiException;
use Illuminate\Support\Facades\Http;

function sendReply(ChannelConnection $connection, int $ruleId, RuleActionType $action = RuleActionType::PublicReply, string $object = 'comment-1'): void
{
    SendReply::dispatchSync(
        channelConnectionId: $connection->id,
        ruleId: $ruleId,
        platform: ChannelPlatform::Facebook,
        surface: WebhookSurface::PostComment,
        sourceObjectId: $object,
        actorId: '999',
        actionType: $action,
        messageTemplate: 'Hi {{commenter_name}}',
        context: ['commenter_name' => 'Sara'],
    );
}

test('a public reply is sent and logged with the rendered template', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'reply-1'])]);
    $connection = ChannelConnection::factory()->facebook()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    sendReply($connection, $rule->id);

    $log = ReplyLog::withoutTenantScope()->firstWhere('source_object_id', 'comment-1');
    expect($log->status)->toBe(ReplyLogStatus::Sent);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'comment-1/comments') && $request['message'] === 'Hi Sara');
});

test('a private reply hits the private_replies edge', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'm-1'])]);
    $connection = ChannelConnection::factory()->facebook()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    sendReply($connection, $rule->id, RuleActionType::PrivateReply);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'comment-1/private_replies'));
});

test('the same object + action is never replied to twice (idempotent)', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['id' => 'reply-1'])]);
    $connection = ChannelConnection::factory()->facebook()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    sendReply($connection, $rule->id);
    sendReply($connection, $rule->id); // retry / duplicate

    Http::assertSentCount(1);
    expect(ReplyLog::withoutTenantScope()->where('source_object_id', 'comment-1')->count())->toBe(1);
});

test('a failed Meta call marks the log failed and rethrows', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'nope', 'code' => 10]], 400)]);
    $connection = ChannelConnection::factory()->facebook()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    expect(fn () => sendReply($connection, $rule->id))->toThrow(MetaApiException::class);

    expect(ReplyLog::withoutTenantScope()->firstWhere('source_object_id', 'comment-1')->status)->toBe(ReplyLogStatus::Failed);
});
