<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionRequestStatus;
use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Provider-agnostic core: turns an approved subscription request into the
 * tenant's single active subscription. Any previously active subscription is
 * expired first, preserving the "at most one active per tenant" invariant.
 *
 * Shared by the manual flow (admin approval) and any future payment gateway.
 */
class ActivateSubscription
{
    public function __construct(protected TenantContext $tenantContext) {}

    public function handle(
        SubscriptionRequest $request,
        SubscriptionSource $source = SubscriptionSource::Manual,
        ?User $reviewer = null,
    ): Subscription {
        $tenant = $request->tenant()->firstOrFail();
        $planPrice = $request->planPrice()->firstOrFail();

        return $this->tenantContext->run($tenant, fn (): Subscription => DB::transaction(function () use ($request, $tenant, $planPrice, $source, $reviewer): Subscription {
            // Expire any currently active subscription for this tenant.
            Subscription::query()
                ->where('status', SubscriptionStatus::Active)
                ->update(['status' => SubscriptionStatus::Expired]);

            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $planPrice->plan_id,
                'duration_months' => $planPrice->duration_months,
                'price' => $planPrice->price,
                'currency' => $planPrice->currency,
                'starts_at' => now(),
                'ends_at' => now()->addMonths($planPrice->duration_months),
                'status' => SubscriptionStatus::Active,
                'source' => $source,
                'created_by' => $reviewer?->id,
            ]);

            $request->update([
                'status' => SubscriptionRequestStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            return $subscription;
        }));
    }
}
