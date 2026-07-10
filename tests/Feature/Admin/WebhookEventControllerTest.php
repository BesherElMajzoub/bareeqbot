<?php

use App\Enums\PlatformRole;
use App\Models\User;
use App\Models\WebhookEvent;

test('guests are redirected from the webhook events inspector', function () {
    $this->get(route('admin.webhook-events.index'))->assertRedirect(route('login'));
});

test('non-staff users cannot view webhook events', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.webhook-events.index'))->assertForbidden();
});

test('a platform staff user without inspect-webhooks permission is forbidden', function () {
    // is_platform_staff clears the platform.staff middleware gate, but no
    // platform role/permission is assigned, so the fine-grained check fails.
    $staff = User::factory()->create(['is_platform_staff' => true]);

    $this->actingAs($staff)->get(route('admin.webhook-events.index'))->assertForbidden();
});

test('support staff can list and view webhook events', function () {
    $support = createPlatformStaff(PlatformRole::Support);
    $event = WebhookEvent::factory()->create();

    $this->actingAs($support)->get(route('admin.webhook-events.index'))->assertOk();
    $this->actingAs($support)->get(route('admin.webhook-events.show', $event))->assertOk();
});
