<?php

use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('platform staff can list subscription requests', function () {
    $staff = User::factory()->create(['is_platform_staff' => true]);
    SubscriptionRequest::factory()->count(2)->create();

    $this->actingAs($staff)->get(route('admin.subscription-requests.index'))->assertOk();
});

test('non-staff users are forbidden from the admin area', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.subscription-requests.index'))->assertForbidden();
});

test('guests are redirected from the admin area', function () {
    $this->get(route('admin.subscription-requests.index'))->assertRedirect(route('login'));
});

test('staff can approve a request over http', function () {
    Notification::fake();
    $staff = User::factory()->create(['is_platform_staff' => true]);
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $request = SubscriptionRequest::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($staff)
        ->post(route('admin.subscription-requests.approve', $request))
        ->assertRedirect();

    expect($request->fresh()->status->value)->toBe('approved');
});

test('staff can reject a request with a reason over http', function () {
    Notification::fake();
    $staff = User::factory()->create(['is_platform_staff' => true]);
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $request = SubscriptionRequest::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($staff)
        ->post(route('admin.subscription-requests.reject', $request), ['reason' => 'Invalid receipt'])
        ->assertRedirect();

    expect($request->fresh()->status->value)->toBe('rejected');
});

test('rejecting requires a reason', function () {
    $staff = User::factory()->create(['is_platform_staff' => true]);
    $request = SubscriptionRequest::factory()->create();

    $this->actingAs($staff)
        ->from(route('admin.subscription-requests.index'))
        ->post(route('admin.subscription-requests.reject', $request), [])
        ->assertSessionHasErrors('reason');
});
