<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('it resolves and shares the current tenant for an authenticated request', function () {
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $user->id]);
    $user->tenants()->attach($tenant->id, ['role' => TenantRole::Owner->value]);
    $user->forceFill(['current_tenant_id' => $tenant->id])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.currentTenant.id', $tenant->id)
            ->where('auth.currentTenant.name', $tenant->name)
            ->where('direction', 'rtl'),
        );
});

test('guests have no tenant bound', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
