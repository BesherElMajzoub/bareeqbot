<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionRequestStatus;
use App\Models\PlanPrice;
use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionRequestSubmitted;
use Illuminate\Support\Facades\Notification;

/**
 * A tenant owner submits a request to subscribe to a plan price, optionally
 * attaching an offline payment receipt. Platform staff are notified to review.
 */
class SubmitSubscriptionRequest
{
    public function handle(
        Tenant $tenant,
        PlanPrice $planPrice,
        ?string $payerNote = null,
        ?string $paymentProofPath = null,
    ): SubscriptionRequest {
        $request = SubscriptionRequest::create([
            'tenant_id' => $tenant->id,
            'plan_price_id' => $planPrice->id,
            'payer_note' => $payerNote,
            'payment_proof_path' => $paymentProofPath,
            'status' => SubscriptionRequestStatus::Pending,
        ]);

        Notification::send(
            User::where('is_platform_staff', true)->get(),
            new SubscriptionRequestSubmitted($request),
        );

        return $request;
    }
}
