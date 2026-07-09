<?php

use App\Enums\TenantRole;
use App\Models\PlanPrice;
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
