<?php

namespace App\Contracts\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;

/**
 * Abstracts how a subscription request becomes an active subscription.
 *
 * v1 ships `ManualProvider` (offline payment + admin activation). A future
 * automated gateway (Moyasar / Tap / PayTabs) implements the same contract and
 * funnels into the same ActivateSubscription action from its payment webhook.
 */
interface BillingProvider
{
    public function key(): string;

    /**
     * Activate the subscription for an approved/paid request.
     */
    public function activate(SubscriptionRequest $request, ?User $reviewer = null): Subscription;
}
