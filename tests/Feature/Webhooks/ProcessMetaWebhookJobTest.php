<?php

use App\Enums\WebhookEventStatus;
use App\Jobs\ProcessMetaWebhook;
use App\Models\ChannelConnection;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use App\Support\TenantContext;

/**
 * @return array{0: Tenant, 1: ChannelConnection}
 */
function connectedTenant(string $pageId = '111', bool $active = true): array
{
    $tenant = Tenant::factory()->create();

    if ($active) {
        Subscription::factory()->create(['tenant_id' => $tenant->id]);
    }

    $connection = ChannelConnection::factory()->facebook()->create([
        'tenant_id' => $tenant->id,
        'provider_account_id' => $pageId,
    ]);

    return [$tenant, $connection];
}

test('resolves the tenant from the asset id and marks the event processed', function () {
    connectedTenant('111');
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->status)->toBe(WebhookEventStatus::Processed)
        ->and($event->fresh()->processed_at)->not->toBeNull();
});

test('skips self-authored comments to avoid loops', function () {
    connectedTenant('111');
    // The commenter id equals the page id → the page acting on itself.
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '111')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->status)->toBe(WebhookEventStatus::Skipped);
});

test('skips events for an unknown asset', function () {
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('does-not-exist', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->status)->toBe(WebhookEventStatus::Skipped);
});

test('skips events for a tenant without an active subscription', function () {
    connectedTenant('111', active: false);
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->status)->toBe(WebhookEventStatus::Skipped);
});

test('skips events for a revoked connection', function () {
    $tenant = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $tenant->id]);
    ChannelConnection::factory()->facebook()->revoked()->create([
        'tenant_id' => $tenant->id,
        'provider_account_id' => '111',
    ]);
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->status)->toBe(WebhookEventStatus::Skipped);
});

test('an already processed event is not processed again', function () {
    connectedTenant('111');
    $event = WebhookEvent::factory()->processed()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);
    $processedAt = $event->processed_at;

    ProcessMetaWebhook::dispatchSync($event->id);

    expect($event->fresh()->processed_at->timestamp)->toBe($processedAt->timestamp);
});

test('does not leave a bound tenant context after running', function () {
    connectedTenant('111');
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    expect(app(TenantContext::class)->has())->toBeFalse();
});
