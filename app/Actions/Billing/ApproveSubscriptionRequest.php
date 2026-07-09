<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\BillingProvider;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Notifications\SubscriptionApproved;
use RuntimeException;

/**
 * Admin approval of a pending request: activates the subscription via the
 * configured billing provider and notifies the tenant owner.
 */
class ApproveSubscriptionRequest
{
    public function __construct(protected BillingProvider $billing) {}

    public function handle(SubscriptionRequest $request, User $reviewer): Subscription
    {
        if (! $request->isPending()) {
            throw new RuntimeException('Only pending requests can be approved.');
        }

        $subscription = $this->billing->activate($request, $reviewer);

        $request->tenant()->firstOrFail()->owner->notify(new SubscriptionApproved($subscription));

        return $subscription;
    }
}
