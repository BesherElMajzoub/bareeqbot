<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

/**
 * Platform-level authorization for managing tenants. Not tenant-scoped —
 * these checks run against platform permissions (super_admin / support),
 * bound via the platform team id by the EnsurePlatformStaff middleware.
 */
class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-tenants');
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->hasPermissionTo('manage-tenants');
    }
}
