<?php

use App\Enums\ReplyLogStatus;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\User;

test('an owner can view their reply log, filtered', function () {
    [$user, $tenant] = createTenantOwner();
    $connection = ChannelConnection::factory()->create(['tenant_id' => $tenant->id]);
    ReplyLog::factory()->create(['tenant_id' => $tenant->id, 'channel_connection_id' => $connection->id, 'status' => ReplyLogStatus::Sent]);

    $this->actingAs($user)
        ->get(route('analytics.logs', ['filter' => ['status' => 'sent']]))
        ->assertOk();
});

test('platform staff can view platform analytics', function () {
    $staff = User::factory()->create(['is_platform_staff' => true]);

    $this->actingAs($staff)->get(route('admin.analytics'))->assertOk();
});

test('non-staff cannot view platform analytics', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.analytics'))->assertForbidden();
});

test('guests are redirected from the reply log', function () {
    $this->get(route('analytics.logs'))->assertRedirect(route('login'));
});
