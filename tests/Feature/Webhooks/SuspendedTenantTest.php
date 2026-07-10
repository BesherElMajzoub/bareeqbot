<?php

use App\Enums\TenantStatus;
use App\Jobs\ProcessMetaWebhook;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\RuleAction;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Bus;

test('a suspended tenant gets no automation even with an active subscription and matching rule', function () {
    Bus::fake([SendReply::class]);

    $tenant = Tenant::factory()->create(['status' => TenantStatus::Suspended]);
    Subscription::factory()->create(['tenant_id' => $tenant->id]);
    $connection = ChannelConnection::factory()->facebook()->create(['tenant_id' => $tenant->id, 'provider_account_id' => '111']);
    AutomationRule::factory()
        ->has(RuleAction::factory()->count(1), 'actions')
        ->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id]);

    $event = WebhookEvent::factory()->create(['raw_payload' => fbCommentPayload('111', 'c1', '999')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertNotDispatched(SendReply::class);
});
