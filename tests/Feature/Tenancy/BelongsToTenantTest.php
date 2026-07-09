<?php

use App\Models\Tenant;
use App\Support\TenantContext;
use Tests\Fixtures\TenancyTestFixture;

test('it auto-fills tenant_id from the active tenant on create', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant);

    $fixture = TenancyTestFixture::create(['name' => 'widget']);

    expect($fixture->tenant_id)->toBe($tenant->id);
});

test('the global scope limits queries to the active tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    app(TenantContext::class)->set($tenantA);
    TenancyTestFixture::create(['name' => 'a1']);
    TenancyTestFixture::create(['name' => 'a2']);

    app(TenantContext::class)->set($tenantB);
    TenancyTestFixture::create(['name' => 'b1']);

    // Active tenant = B → only B's row is visible.
    expect(TenancyTestFixture::count())->toBe(1);

    // Switch to A → only A's rows.
    app(TenantContext::class)->set($tenantA);
    expect(TenancyTestFixture::count())->toBe(2);

    // Bypass the scope → all rows across tenants.
    expect(TenancyTestFixture::withoutTenantScope()->count())->toBe(3);
});

test('the scope does not constrain when no tenant is bound', function () {
    $tenant = Tenant::factory()->create();
    app(TenantContext::class)->set($tenant);
    TenancyTestFixture::create(['name' => 'a']);

    app(TenantContext::class)->forget();

    expect(TenancyTestFixture::count())->toBe(1);
});
