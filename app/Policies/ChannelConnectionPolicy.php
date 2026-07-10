<?php

namespace App\Policies;

use App\Models\ChannelConnection;
use App\Models\User;
use App\Services\Billing\SubscriptionQuota;

/**
 * Authorization policy for ChannelConnection.
 *
 * Important: SubstituteBindings runs before SetCurrentTenant, so implicit
 * route-model bindings are NOT tenant-scoped. The delete() check therefore
 * verifies tenant_id explicitly rather than relying on the ambient scope.
 */
class ChannelConnectionPolicy
{
    public function __construct(
        private readonly SubscriptionQuota $quota,
    ) {}

    /**
     * Any tenant member with manage-connections can see the connections list.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage-connections');
    }

    /**
     * Creating a connection also requires that the quota has not been exceeded.
     */
    public function create(User $user): bool
    {
        if (! $user->hasPermissionTo('manage-connections')) {
            return false;
        }

        // Tenant is guaranteed to be loaded by SetCurrentTenant at this point.
        $tenant = $user->tenants()->first();
        if ($tenant === null) {
            return false;
        }

        return $this->quota->canConnectMore($tenant);
    }

    /**
     * Delete requires: permission + the connection must belong to the user's tenant.
     * We do NOT rely on the global scope here (route-binding caveat).
     */
    public function delete(User $user, ChannelConnection $connection): bool
    {
        if (! $user->hasPermissionTo('manage-connections')) {
            return false;
        }

        // Explicit tenant_id check — the ambient scope may not be active yet.
        $tenant = $user->tenants()->first();

        return $tenant !== null && $connection->tenant_id === $tenant->id;
    }
}
