<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Enums\TenantRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Tenant-scoped abilities (owner / member).
     *
     * @var list<string>
     */
    protected array $tenantPermissions = [
        'manage-billing',
        'manage-connections',
        'manage-rules',
        'view-analytics',
        'manage-team',
    ];

    /**
     * Platform-scoped abilities (super_admin / support).
     *
     * @var list<string>
     */
    protected array $platformPermissions = [
        'view-tenants',
        'manage-tenants',
        'approve-subscriptions',
        'view-global-analytics',
        'inspect-webhooks',
    ];

    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // Roles/permissions are created team-agnostic (tenant_id = null);
        // it is the *assignment* that is scoped to a tenant team.
        $registrar->setPermissionsTeamId(null);

        foreach ([...$this->tenantPermissions, ...$this->platformPermissions] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Refresh the cache so the roles below can resolve the new permissions.
        $registrar->forgetCachedPermissions();

        // --- Tenant roles ---
        Role::findOrCreate(TenantRole::Owner->value, 'web')
            ->syncPermissions($this->tenantPermissions);

        Role::findOrCreate(TenantRole::Member->value, 'web')
            ->syncPermissions(['manage-connections', 'manage-rules', 'view-analytics']);

        // --- Platform roles ---
        Role::findOrCreate(PlatformRole::SuperAdmin->value, 'web')
            ->syncPermissions(Permission::all());

        Role::findOrCreate(PlatformRole::Support->value, 'web')
            ->syncPermissions(['view-tenants', 'approve-subscriptions', 'view-global-analytics', 'inspect-webhooks']);

        $registrar->forgetCachedPermissions();
    }
}
