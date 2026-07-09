<?php

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the base TestCase and RefreshDatabase to the Feature and Unit
| suites so every test runs against a fresh, migrated database. Foundational
| reference data (roles/permissions) is seeded from TestCase::setUp so that
| both Pest and class-based tests get it.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user who owns a fresh tenant, with the tenant `owner` role assigned
 * within that tenant's team. Returns [User, Tenant].
 *
 * @return array{0: User, 1: Tenant}
 */
function createTenantOwner(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $user->id]);
    $user->tenants()->attach($tenant->id, ['role' => TenantRole::Owner->value]);
    $user->forceFill(['current_tenant_id' => $tenant->id])->save();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole(TenantRole::Owner->value);

    return [$user, $tenant];
}
