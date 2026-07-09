<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the tenant owner when their subscription is activated.
 */
class SubscriptionApproved extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription) {}

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
            'type' => 'subscription_approved',
            'subscription_id' => $this->subscription->id,
            'tenant_id' => $this->subscription->tenant_id,
            'ends_at' => $this->subscription->ends_at->toIso8601String(),
        ];
    }
}
