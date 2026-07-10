<?php

use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\Tenant;
use App\Services\Analytics\AnalyticsService;

test('summary computes counts and success rate for a tenant', function () {
    $tenant = Tenant::factory()->create();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);

    ReplyLog::factory()->count(4)->create([
        'tenant_id' => $tenant->id,
        'channel_connection_id' => $connection->id,
        'action_type' => RuleActionType::PublicReply,
        'status' => ReplyLogStatus::Sent,
    ]);
    ReplyLog::factory()->create([
        'tenant_id' => $tenant->id,
        'channel_connection_id' => $connection->id,
        'status' => ReplyLogStatus::Failed,
    ]);

    $summary = app(AnalyticsService::class)->summary($tenant->id);

    expect($summary['replies_sent'])->toBe(4)
        ->and($summary['failures'])->toBe(1)
        ->and($summary['success_rate'])->toBe(80.0);
});

test('summary is isolated per tenant', function () {
    $tenant = Tenant::factory()->create();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    ReplyLog::factory()->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id, 'status' => ReplyLogStatus::Sent]);

    $other = Tenant::factory()->create();
    $otherConnection = ChannelConnection::factory()->create(['tenant_id' => $other->id]);
    ReplyLog::factory()->count(5)->create(['tenant_id' => $other->id, 'channel_connection_id' => $otherConnection->id, 'status' => ReplyLogStatus::Sent]);

    expect(app(AnalyticsService::class)->summary($tenant->id)['replies_sent'])->toBe(1)
        ->and(app(AnalyticsService::class)->summary(null)['replies_sent'])->toBe(6);
});

test('the daily series is zero-filled for the window', function () {
    $tenant = Tenant::factory()->create();

    $series = app(AnalyticsService::class)->dailySeries($tenant->id, 7);

    expect($series)->toHaveCount(7)
        ->and($series[0])->toHaveKeys(['date', 'replies', 'dms', 'failures']);
});
