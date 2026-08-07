<?php

use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\RuleAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

test('a message-surface rule accepts a dm action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->post(route('rules.store'), rulePayload($connection->id, [
            'trigger_surface' => 'message',
            'match_type' => 'contains',
            'keyword' => 'price',
            'actions' => [['action_type' => 'dm', 'message_template' => 'hi', 'delay_seconds' => 0]],
        ]))
        ->assertRedirect(route('rules.index'));

    expect(AutomationRule::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('a message-surface rule rejects a public_reply action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), rulePayload($connection->id, ['trigger_surface' => 'message']))
        ->assertSessionHasErrors('actions.0.action_type');
});

test('a non-comment rule cannot target a specific post', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), rulePayload($connection->id, [
            'trigger_surface' => 'message',
            'target_scope' => 'specific',
            'target_ref' => 'm1',
            'actions' => [['action_type' => 'dm', 'message_template' => 'hi', 'delay_seconds' => 0]],
        ]))
        ->assertSessionHasErrors('target_scope');
});

test('an owner can attach an image to a private reply action', function () {
    Storage::fake('public');
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    $image = UploadedFile::fake()->image('promo.jpg');

    $this->actingAs($user)
        ->post(route('rules.store'), rulePayload($connection->id, [
            'actions' => [[
                'action_type' => 'private_reply',
                'message_template' => 'hi',
                'delay_seconds' => 0,
                'image' => $image,
            ]],
        ]))
        ->assertRedirect(route('rules.index'));

    $action = RuleAction::sole();
    expect($action->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($action->image_path);
});

test('an image is rejected on a public_reply action', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    $image = UploadedFile::fake()->image('promo.jpg');

    $this->actingAs($user)
        ->from(route('rules.index'))
        ->post(route('rules.store'), rulePayload($connection->id, [
            'actions' => [[
                'action_type' => 'public_reply',
                'message_template' => 'hi',
                'delay_seconds' => 0,
                'image' => $image,
            ]],
        ]))
        ->assertSessionHasErrors('actions.0.image');
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

test('an owner can list a connection\'s posts for the target dropdown', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->facebook()->create(['tenant_id' => $tenant->id]);

    Http::fake(['graph.facebook.com/*' => Http::response([
        'data' => [
            ['id' => '111_222', 'message' => 'First post', 'created_time' => '2026-08-01T00:00:00+0000'],
            ['id' => '111_333', 'message' => 'Second post', 'created_time' => '2026-08-02T00:00:00+0000'],
        ],
    ])]);

    $this->actingAs($user)
        ->getJson(route('rules.posts', ['channel_connection_id' => $connection->id]))
        ->assertOk()
        ->assertJson(['posts' => [
            ['id' => '111_222', 'title' => 'First post', 'created_time' => '2026-08-01T00:00:00+0000'],
            ['id' => '111_333', 'title' => 'Second post', 'created_time' => '2026-08-02T00:00:00+0000'],
        ]]);
});

test('a user cannot list posts for another tenant\'s connection', function () {
    [$user] = createTenantOwner();
    $otherConnection = ChannelConnection::factory()->create();

    $this->actingAs($user)
        ->getJson(route('rules.posts', ['channel_connection_id' => $otherConnection->id]))
        ->assertNotFound();
});

test('a failed Meta call returns an empty post list instead of erroring', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->facebook()->create(['tenant_id' => $tenant->id]);

    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'nope', 'code' => 10]], 400)]);

    $this->actingAs($user)
        ->getJson(route('rules.posts', ['channel_connection_id' => $connection->id]))
        ->assertOk()
        ->assertJson(['posts' => []]);
});
