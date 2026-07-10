<?php

use App\Jobs\ProcessMetaWebhook;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\RuleAction;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Bus;

/**
 * @return array{0: Tenant, 1: ChannelConnection}
 */
function tenantWithRule(string $keyword, string $pageId = '111'): array
{
    $tenant = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $tenant->id]);
    $connection = ChannelConnection::factory()->facebook()->create([
        'tenant_id' => $tenant->id,
        'provider_account_id' => $pageId,
    ]);
    AutomationRule::factory()
        ->contains($keyword)
        ->has(RuleAction::factory()->count(1), 'actions')
        ->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id]);

    return [$tenant, $connection];
}

test('a comment matching a rule queues a reply job', function () {
    Bus::fake([SendReply::class]);
    tenantWithRule('price');
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999', 'what is the price?')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertDispatched(SendReply::class);
});

test('a comment matching no rule queues nothing', function () {
    Bus::fake([SendReply::class]);
    tenantWithRule('price');
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999', 'hello there')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertNotDispatched(SendReply::class);
});

test('a self-authored comment never queues a reply', function () {
    Bus::fake([SendReply::class]);
    tenantWithRule('price');
    // Commenter id equals the page id → the page acting on itself.
    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '111', 'the price is great')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertNotDispatched(SendReply::class);
});
