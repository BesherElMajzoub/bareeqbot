<?php

use App\Models\AutomationRule;
use App\Models\ChannelConnection;

function storyRulePayload(int $connectionId, string $actionType): array
{
    return [
        'channel_connection_id' => $connectionId,
        'name' => 'Story DM',
        'trigger_surface' => 'story_reply',
        'target_scope' => 'all',
        'match_type' => 'any',
        'priority' => 0,
        'is_active' => true,
        'actions' => [[
            'action_type' => $actionType,
            'message_template' => 'Thanks for watching!',
            'delay_seconds' => 0,
        ]],
    ];
}

test('a story rule accepts a dm action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->instagram()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->post(route('rules.store'), storyRulePayload($connection->id, 'dm'))
        ->assertRedirect(route('rules.index'));

    expect(AutomationRule::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('a story rule rejects a public_reply action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->instagram()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), storyRulePayload($connection->id, 'public_reply'))
        ->assertSessionHasErrors('actions.0.action_type');
});
