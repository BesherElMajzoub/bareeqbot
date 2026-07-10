<?php

use App\Enums\SubscriptionRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantRole;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;

test('guests are redirected from billing to login', function () {
    $this->get(route('billing.index'))->assertRedirect(route('login'));
});

test('a tenant owner can view the billing page', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)->get(route('billing.index'))->assertOk();
});

test('a tenant owner can submit a subscription request', function () {
    Notification::fake();
    [$user, $tenant] = createTenantOwner();
    $price = PlanPrice::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->post(route('billing.requests.store'), ['plan_price_id' => $price->id, 'payer_note' => 'paid via bank'])
        ->assertRedirect(route('billing.index'));

    expect(SubscriptionRequest::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('a member without billing permission cannot submit a request', function () {
    [$owner, $tenant] = createTenantOwner();
    $member = User::factory()->create(['current_tenant_id' => $tenant->id]);
    $member->tenants()->attach($tenant->id, ['role' => TenantRole::Member->value]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $member->assignRole(TenantRole::Member->value);

    $price = PlanPrice::factory()->create(['is_active' => true]);

    $this->actingAs($member)
        ->post(route('billing.requests.store'), ['plan_price_id' => $price->id])
        ->assertForbidden();
});

test('submitting requires a valid active plan price', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)
        ->from(route('billing.index'))
        ->post(route('billing.requests.store'), ['plan_price_id' => 999999])
        ->assertSessionHasErrors('plan_price_id');
});

test('a tenant with an active subscription cannot submit another request', function () {
    [$user, $tenant] = createTenantOwner();
    Subscription::factory()->create(['tenant_id' => $tenant->id, 'status' => SubscriptionStatus::Active]);
    $price = PlanPrice::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->from(route('billing.index'))
        ->post(route('billing.requests.store'), ['plan_price_id' => $price->id])
        ->assertSessionHasErrors('plan_price_id');

    expect(SubscriptionRequest::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(0);
});

test('a tenant with a pending request cannot submit another request', function () {
    [$user, $tenant] = createTenantOwner();
    $firstPrice = PlanPrice::factory()->create(['is_active' => true]);
    SubscriptionRequest::create([
        'tenant_id' => $tenant->id,
        'plan_price_id' => $firstPrice->id,
        'status' => SubscriptionRequestStatus::Pending,
    ]);
    $secondPrice = PlanPrice::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->from(route('billing.index'))
        ->post(route('billing.requests.store'), ['plan_price_id' => $secondPrice->id])
        ->assertSessionHasErrors('plan_price_id');

    expect(SubscriptionRequest::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

test('a tenant owner can cancel their active subscription', function () {
    [$user, $tenant] = createTenantOwner();
    $subscription = Subscription::factory()->create(['tenant_id' => $tenant->id, 'status' => SubscriptionStatus::Active]);

    $this->actingAs($user)
        ->post(route('billing.subscription.cancel'))
        ->assertRedirect(route('billing.index'));

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Cancelled);
});

test('cancelling without an active subscription 404s', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)
        ->post(route('billing.subscription.cancel'))
        ->assertNotFound();
});

test('a member without billing permission cannot cancel a subscription', function () {
    [, $tenant] = createTenantOwner();
    Subscription::factory()->create(['tenant_id' => $tenant->id, 'status' => SubscriptionStatus::Active]);
    $member = User::factory()->create(['current_tenant_id' => $tenant->id]);
    $member->tenants()->attach($tenant->id, ['role' => TenantRole::Member->value]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $member->assignRole(TenantRole::Member->value);

    $this->actingAs($member)
        ->post(route('billing.subscription.cancel'))
        ->assertForbidden();
});

test('after cancelling a subscription the tenant can submit a new request', function () {
    [$user, $tenant] = createTenantOwner();
    Subscription::factory()->create(['tenant_id' => $tenant->id, 'status' => SubscriptionStatus::Active]);

    $this->actingAs($user)->post(route('billing.subscription.cancel'));

    $price = PlanPrice::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->post(route('billing.requests.store'), ['plan_price_id' => $price->id])
        ->assertRedirect(route('billing.index'));

    expect(SubscriptionRequest::withoutTenantScope()->where('tenant_id', $tenant->id)->count())->toBe(1);
});
