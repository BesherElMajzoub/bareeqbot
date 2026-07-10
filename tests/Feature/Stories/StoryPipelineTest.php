<?php

use App\Enums\ChannelPlatform;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\RuleMatchType;
use App\Enums\WebhookSurface;
use App\Jobs\ProcessMetaWebhook;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\RuleAction;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

/**
 * @return array{0: Tenant, 1: ChannelConnection}
 */
function instagramTenantWithStoryRule(WebhookSurface $surface, string $igId = '333'): array
{
    $tenant = Tenant::factory()->create();
    Subscription::factory()->create(['tenant_id' => $tenant->id]);
    $connection = ChannelConnection::factory()->instagram()->create([
        'tenant_id' => $tenant->id,
        'provider_account_id' => $igId,
    ]);
    AutomationRule::factory()
        ->has(RuleAction::factory()->count(1)->state(['action_type' => RuleActionType::Dm]), 'actions')
        ->create([
            'tenant_id' => $tenant->id,
            'channel_connection_id' => $connection->id,
            'trigger_surface' => $surface,
            'match_type' => RuleMatchType::Any,
        ]);

    return [$tenant, $connection];
}

test('a story reply matching a rule queues a DM with the story reference', function () {
    Bus::fake([SendReply::class]);
    instagramTenantWithStoryRule(WebhookSurface::StoryReply);
    $event = WebhookEvent::factory()->create(['raw_payload' => storyReplyPayload('333', 'msg-1', '777')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertDispatched(SendReply::class, fn (SendReply $job) => $job->actionType === RuleActionType::Dm
        && $job->actorId === '777'
        && $job->parentRef === 'story-1');
});

test('a story mention matching a rule queues a DM', function () {
    Bus::fake([SendReply::class]);
    instagramTenantWithStoryRule(WebhookSurface::StoryMention);
    $event = WebhookEvent::factory()->create(['raw_payload' => storyMentionPayload('333', 'msg-2', '888')]);

    ProcessMetaWebhook::dispatchSync($event->id);

    Bus::assertDispatched(SendReply::class, fn (SendReply $job) => $job->surface === WebhookSurface::StoryMention
        && $job->actionType === RuleActionType::Dm);
});

test('a DM action sends via the messages endpoint and logs the story reference', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'm-1'])]);
    $connection = ChannelConnection::factory()->instagram()->create(['provider_account_id' => '333']);
    $rule = AutomationRule::factory()->create(['tenant_id' => $connection->tenant_id, 'channel_connection_id' => $connection->id]);

    SendReply::dispatchSync(
        channelConnectionId: $connection->id,
        ruleId: $rule->id,
        platform: ChannelPlatform::Instagram,
        surface: WebhookSurface::StoryReply,
        sourceObjectId: 'msg-1',
        actorId: '777',
        actionType: RuleActionType::Dm,
        messageTemplate: 'Thanks for the mention!',
        context: [],
        parentRef: 'story-1',
    );

    Http::assertSent(function ($request) {
        $data = $request->data();

        return str_contains($request->url(), '333/messages')
            && $data['recipient']['id'] === '777'
            && $data['message']['text'] === 'Thanks for the mention!';
    });

    $log = ReplyLog::withoutTenantScope()->firstWhere('source_object_id', 'msg-1');
    expect($log->status)->toBe(ReplyLogStatus::Sent)
        ->and($log->parent_ref)->toBe('story-1');
});
