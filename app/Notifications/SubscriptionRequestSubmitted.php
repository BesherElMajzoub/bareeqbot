<?php

namespace App\Notifications;

use App\Models\SubscriptionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to platform staff when a tenant submits a subscription request.
 */
class SubscriptionRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(public SubscriptionRequest $subscriptionRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_request_submitted',
            'subscription_request_id' => $this->subscriptionRequest->id,
            'tenant_id' => $this->subscriptionRequest->tenant_id,
        ];
    }
}
