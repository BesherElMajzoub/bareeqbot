<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;

/**
 * Connecting a channel requires an active subscription with spare quota.
 * That is an expected business state, not an authorization failure — the user
 * must get a readable explanation instead of a bare 403.
 */
test('the connections page reports no_subscription when there is no active subscription', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)
        ->get(route('connections.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('blockReason', 'no_subscription'));
});

test('starting the facebook oauth flow without a subscription redirects to billing instead of 403', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)
        ->get(route('connections.facebook.redirect'))
        ->assertRedirect(route('billing.index'));
});

test('the connections page reports quota_exceeded when the plan is full', function () {
    [$user, $tenant] = createTenantOwner();
    $plan = Plan::factory()->create(['max_pages' => 0]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $this->actingAs($user)
        ->get(route('connections.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('blockReason', 'quota_exceeded'));
});

test('the connect button is unblocked when an active subscription has spare quota', function () {
    [$user, $tenant] = createTenantOwner();
    $plan = Plan::factory()->create(['max_pages' => 5]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $this->actingAs($user)
        ->get(route('connections.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('blockReason', null));
});

test('a user with an active subscription is sent to facebook, not redirected away', function () {
    [$user, $tenant] = createTenantOwner();
    $plan = Plan::factory()->create(['max_pages' => 5]);
    Subscription::factory()->create([
        'tenant_id' => $tenant->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Active,
    ]);

    $response = $this->actingAs($user)->get(route('connections.facebook.redirect'));

    $response->assertRedirectContains('facebook.com');
});
