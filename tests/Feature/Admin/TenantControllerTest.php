<?php

use App\Enums\PlatformRole;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;

test('guests are redirected from the tenants list', function () {
    $this->get(route('admin.tenants.index'))->assertRedirect(route('login'));
});

test('non-staff users cannot view the tenants list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.tenants.index'))->assertForbidden();
});

test('support staff can view tenants but cannot suspend one', function () {
    $support = createPlatformStaff(PlatformRole::Support);
    $tenant = Tenant::factory()->create();

    $this->actingAs($support)->get(route('admin.tenants.index'))->assertOk();
    $this->actingAs($support)->post(route('admin.tenants.suspend', $tenant))->assertForbidden();
});

test('a super admin can suspend and reactivate a tenant', function () {
    $admin = createPlatformStaff();
    $tenant = Tenant::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenants.suspend', $tenant))
        ->assertRedirect();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->actingAs($admin)
        ->post(route('admin.tenants.activate', $tenant))
        ->assertRedirect();

    expect($tenant->fresh()->status)->toBe(TenantStatus::Active);
});
