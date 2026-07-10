<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

/**
 * A tenant owner cancels their own active subscription so they can request a
 * different plan. Cancellation is immediate (no ends_at grace period).
 */
class CancelSubscription
{
    public function handle(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'ends_at' => now(),
        ]);

        return $subscription;
    }
}
