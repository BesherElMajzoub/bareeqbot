<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the /admin area: only Bariq staff (super_admin / support) may enter.
 * Platform staff act across all tenants, so the ambient tenant context is
 * cleared and the platform team id is bound for Spatie role checks.
 */
class EnsurePlatformStaff
{
    public function __construct(
        protected PermissionRegistrar $permissions,
        protected TenantContext $tenantContext,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null && $user->is_platform_staff, 403);

        $this->tenantContext->forget();
        $this->permissions->setPermissionsTeamId(config('bariq.platform_team_id'));

        return $next($request);
    }
}
