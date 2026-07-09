<?php

namespace App\Services\Billing;

use App\Actions\Billing\ActivateSubscription;
use App\Contracts\Billing\BillingProvider;
use App\Enums\SubscriptionSource;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\User;

/**
 * Manual billing: payment happens offline and a Bariq admin activates the
 * subscription by approving the request.
 */
class ManualProvider implements BillingProvider
{
    public function __construct(protected ActivateSubscription $activate) {}

    public function key(): string
    {
        return 'manual';
    }

    public function activate(SubscriptionRequest $request, ?User $reviewer = null): Subscription
    {
        return $this->activate->handle($request, SubscriptionSource::Manual, $reviewer);
    }
}
