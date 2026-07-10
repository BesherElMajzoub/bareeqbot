<?php

namespace App\Actions\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Tenant;

/**
 * Reactivates a previously suspended tenant.
 */
class ActivateTenant
{
    public function handle(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => TenantStatus::Active]);

        return $tenant;
    }
}
