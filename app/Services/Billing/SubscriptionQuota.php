<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The single source of truth for "can this tenant do X" checks. The quota
 * metric is the number of connected pages/accounts allowed by the tenant's
 * active plan. Enforced when connecting a channel and when running automation.
 */
class SubscriptionQuota
{
    public function activeSubscription(Tenant $tenant): ?Subscription
    {
        return $tenant->activeSubscription();
    }

    public function hasActiveSubscription(Tenant $tenant): bool
    {
        return $this->activeSubscription($tenant) !== null;
    }

    /**
     * Pages allowed by the active plan (0 when there is no active subscription).
     */
    public function maxPages(Tenant $tenant): int
    {
        $subscription = $this->activeSubscription($tenant);

        return $subscription?->plan()->value('max_pages') ?? 0;
    }

    /**
     * Connected pages/accounts currently in use. Channel connections arrive in
     * Phase 3; until that table exists this is 0.
     */
    public function usedPages(Tenant $tenant): int
    {
        if (! Schema::hasTable('channel_connections')) {
            return 0;
        }

        return DB::table('channel_connections')
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->count();
    }

    public function remainingPages(Tenant $tenant): int
    {
        return max(0, $this->maxPages($tenant) - $this->usedPages($tenant));
    }

    public function canConnectMore(Tenant $tenant): bool
    {
        return $this->hasActiveSubscription($tenant)
            && $this->usedPages($tenant) < $this->maxPages($tenant);
    }
}
