<?php

use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\RuleAction;

function rulePayload(int $connectionId, array $overrides = []): array
{
    return array_merge([
        'channel_connection_id' => $connectionId,
        'name' => 'Price replies',
        'trigger_surface' => 'post_comment',
        'target_scope' => 'all',
        'match_type' => 'contains',
        'keyword' => 'price',
        'priority' => 5,
        'is_active' => true,
        'actions' => [[
            'action_type' => 'public_reply',
            'message_template' => 'Hi {{commenter_name}}',
            'delay_seconds' => 0,
        ]],
    ], $overrides);
}

test('guests are redirected from rules', function () {
    $this->get(route('rules.index'))->assertRedirect(route('login'));
});

test('an owner can view the rules page', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)->get(route('rules.index'))->assertOk();
});

test('an owner can create a rule with an action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->post(route('rules.store'), rulePayload($connection->id))
        ->assertRedirect(route('rules.index'));

    expect(AutomationRule::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and(RuleAction::count())->toBe(1);
});

test('a dm action is rejected for a comment rule', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), rulePayload($connection->id, [
            'actions' => [['action_type' => 'dm', 'message_template' => 'hi', 'delay_seconds' => 0]],
        ]))
        ->assertSessionHasErrors('actions.0.action_type');
});

test('a rule cannot target another tenant\'s connection', function () {
    [$user] = createTenantOwner();
    $otherConnection = ChannelConnection::factory()->create();

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), rulePayload($otherConnection->id))
        ->assertSessionHasErrors('channel_connection_id');
});

test('an owner can delete their own rule', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    $rule = AutomationRule::factory()->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id]);

    $this->actingAs($user)
        ->delete(route('rules.destroy', $rule))
        ->assertRedirect(route('rules.index'));

    expect(AutomationRule::withoutTenantScope()->whereKey($rule->id)->exists())->toBeFalse();
});

test('a user cannot delete another tenant\'s rule', function () {
    [$user] = createTenantOwner();
    $otherRule = AutomationRule::factory()->create();

    $this->actingAs($user)
        ->delete(route('rules.destroy', $otherRule))
        ->assertForbidden();

    expect(AutomationRule::withoutTenantScope()->whereKey($otherRule->id)->exists())->toBeTrue();
});
