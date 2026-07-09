<?php

namespace App\Actions\Tenancy;

use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Provisions a new tenant for an owner: creates the tenant, records the
 * membership, assigns the tenant-scoped `owner` role, and makes it the
 * user's current tenant.
 */
class CreateTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PermissionRegistrar $permissions,
    ) {}

    public function handle(User $owner, string $name): Tenant
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $this->uniqueSlug($name),
            'owner_user_id' => $owner->id,
        ]);

        $owner->tenants()->attach($tenant->id, ['role' => TenantRole::Owner->value]);
        $owner->forceFill(['current_tenant_id' => $tenant->id])->save();

        // Assign the owner role within this tenant's team context.
        $this->permissions->setPermissionsTeamId($tenant->id);
        $owner->assignRole(TenantRole::Owner->value);

        // Bind as the active tenant for the remainder of the request.
        $this->tenantContext->set($tenant);

        return $tenant;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tenant';
        $slug = $base;
        $suffix = 1;

        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
