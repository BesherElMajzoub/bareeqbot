<?php

use App\Actions\Connections\MarkConnectionError;
use App\Enums\ChannelPlatform;
use App\Enums\ChannelStatus;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ChannelConnectionRevoked;
use App\Services\Meta\MetaApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

test('an expired Meta token marks the connection errored and notifies the owner, without rethrowing', function () {
    Notification::fake();
    Http::fake(['graph.facebook.com/*' => Http::response([
        'error' => ['message' => 'Error validating access token', 'type' => 'OAuthException', 'code' => 190],
    ], 400)]);

    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $connection = ChannelConnection::factory()->facebook()->create(['tenant_id' => $tenant->id]);
    $rule = AutomationRule::factory()->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id]);

    SendReply::dispatchSync(
        channelConnectionId: $connection->id,
        ruleId: $rule->id,
        platform: ChannelPlatform::Facebook,
        surface: WebhookSurface::PostComment,
        sourceObjectId: 'c1',
        actorId: '999',
        actionType: RuleActionType::PublicReply,
        messageTemplate: 'hi',
        context: [],
    );

    expect($connection->fresh()->status)->toBe(ChannelStatus::Error);
    expect(ReplyLog::withoutTenantScope()->firstWhere('source_object_id', 'c1')->status)->toBe(ReplyLogStatus::Failed);
    Notification::assertSentTo($owner, ChannelConnectionRevoked::class);
});

test('marking an already-errored connection does not notify twice', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $connection = ChannelConnection::factory()->facebook()->create(['tenant_id' => $tenant->id, 'status' => ChannelStatus::Error]);

    app(MarkConnectionError::class)->handle($connection, 'still broken');

    Notification::assertNothingSent();
});

test('a transient (non-auth) Meta error does not revoke the connection and is rethrown for retry', function () {
    Http::fake(['graph.facebook.com/*' => Http::response([
        'error' => ['message' => 'Service unavailable', 'type' => 'ServerError', 'code' => 2],
    ], 500)]);

    $connection = ChannelConnection::factory()->facebook()->create();
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    expect(fn () => SendReply::dispatchSync(
        channelConnectionId: $connection->id,
        ruleId: $rule->id,
        platform: ChannelPlatform::Facebook,
        surface: WebhookSurface::PostComment,
        sourceObjectId: 'c2',
        actorId: '999',
        actionType: RuleActionType::PublicReply,
        messageTemplate: 'hi',
        context: [],
    ))->toThrow(MetaApiException::class);

    expect($connection->fresh()->status)->toBe(ChannelStatus::Active);
});
