<?php

use App\Enums\WebhookSurface;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Services\Automation\RuleMatcher;

beforeEach(function () {
    $this->matcher = new RuleMatcher;
    $this->connection = ChannelConnection::factory()->create();
});

test('an "any" rule matches any comment text', function () {
    AutomationRule::factory()->create(['channel_connection_id' => $this->connection->id]);

    $rule = $this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'literally anything');

    expect($rule)->not->toBeNull();
});

test('a contains rule matches case-insensitively by default', function () {
    AutomationRule::factory()->contains('price')->create(['channel_connection_id' => $this->connection->id]);

    expect($this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'What is the PRICE?'))->not->toBeNull()
        ->and($this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'hello there'))->toBeNull();
});

test('inactive rules never match', function () {
    AutomationRule::factory()->inactive()->create(['channel_connection_id' => $this->connection->id]);

    expect($this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'hi'))->toBeNull();
});

test('the highest priority matching rule wins', function () {
    AutomationRule::factory()->create(['channel_connection_id' => $this->connection->id, 'name' => 'low', 'priority' => 1]);
    AutomationRule::factory()->create(['channel_connection_id' => $this->connection->id, 'name' => 'high', 'priority' => 10]);

    expect($this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'hi')->name)->toBe('high');
});

test('a specific-post rule only matches its target post', function () {
    AutomationRule::factory()->forPost('post-123')->create(['channel_connection_id' => $this->connection->id]);

    expect($this->matcher->match($this->connection, WebhookSurface::PostComment, 'post-123', 'hi'))->not->toBeNull()
        ->and($this->matcher->match($this->connection, WebhookSurface::PostComment, 'other-post', 'hi'))->toBeNull();
});

test('rules for a different surface do not match', function () {
    AutomationRule::factory()->create([
        'channel_connection_id' => $this->connection->id,
        'trigger_surface' => WebhookSurface::StoryReply,
    ]);

    expect($this->matcher->match($this->connection, WebhookSurface::PostComment, null, 'hi'))->toBeNull();
});
