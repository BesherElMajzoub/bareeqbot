<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionRequestStatus;
use App\Models\SubscriptionRequest;
use App\Models\User;
use App\Notifications\SubscriptionRejected;
use RuntimeException;

/**
 * Admin rejection of a pending request with a reason. The tenant owner is notified.
 */
class RejectSubscriptionRequest
{
    public function handle(SubscriptionRequest $request, User $reviewer, string $reason): SubscriptionRequest
    {
        if (! $request->isPending()) {
            throw new RuntimeException('Only pending requests can be rejected.');
        }

        $request->update([
            'status' => SubscriptionRequestStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reject_reason' => $reason,
        ]);

        $request->tenant()->firstOrFail()->owner->notify(new SubscriptionRejected($request));

        return $request;
    }
}
