<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active tenant for authenticated web/API requests from the user,
 * binds it into the TenantContext, and scopes Spatie role checks to that tenant.
 *
 * Webhook jobs do NOT go through this middleware — they set the tenant manually.
 */
class SetCurrentTenant
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $tenant = $user->currentTenant
                ?? $user->ownedTenants()->first()
                ?? $user->tenants()->first();

            if ($tenant !== null) {
                $this->tenantContext->set($tenant);
                $this->permissions->setPermissionsTeamId($tenant->id);
            }
        }

        return $next($request);
    }
}
