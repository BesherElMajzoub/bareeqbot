<?php

namespace App\Actions\Tenancy;

use App\Enums\TenantStatus;
use App\Models\Tenant;

/**
 * Suspends a tenant — an admin kill switch. ProcessMetaWebhook checks
 * Tenant::isActive() and skips automation entirely for suspended tenants,
 * independent of their subscription status.
 */
class SuspendTenant
{
    public function handle(Tenant $tenant): Tenant
    {
        $tenant->update(['status' => TenantStatus::Suspended]);

        return $tenant;
    }
}
