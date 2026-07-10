<?php

use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Models\ChannelConnection;
use App\Models\DailyStat;
use App\Models\ReplyLog;
use App\Models\Tenant;

function seedYesterdayLogs(Tenant $tenant, ChannelConnection $connection): void
{
    $yesterday = now()->subDay();

    ReplyLog::factory()->count(3)->create([
        'tenant_id' => $tenant->id,
        'channel_connection_id' => $connection->id,
        'action_type' => RuleActionType::PublicReply,
        'status' => ReplyLogStatus::Sent,
        'created_at' => $yesterday,
    ]);
    ReplyLog::factory()->create([
        'tenant_id' => $tenant->id,
        'channel_connection_id' => $connection->id,
        'action_type' => RuleActionType::Dm,
        'status' => ReplyLogStatus::Sent,
        'created_at' => $yesterday,
    ]);
    ReplyLog::factory()->create([
        'tenant_id' => $tenant->id,
        'channel_connection_id' => $connection->id,
        'status' => ReplyLogStatus::Failed,
        'created_at' => $yesterday,
    ]);
}

test('stats:aggregate rolls reply_logs into daily_stats', function () {
    $tenant = Tenant::factory()->create();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    seedYesterdayLogs($tenant, $connection);

    $this->artisan('stats:aggregate')->assertSuccessful();

    $stat = DailyStat::withoutTenantScope()->where('tenant_id', $tenant->id)->first();
    expect($stat)->not->toBeNull()
        ->and($stat->replies_sent)->toBe(3)
        ->and($stat->dms_sent)->toBe(1)
        ->and($stat->failures)->toBe(1)
        ->and($stat->events_received)->toBe(5);
});

test('stats:aggregate is idempotent when re-run', function () {
    $tenant = Tenant::factory()->create();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    seedYesterdayLogs($tenant, $connection);

    $this->artisan('stats:aggregate')->assertSuccessful();
    $this->artisan('stats:aggregate')->assertSuccessful();

    expect(DailyStat::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});
