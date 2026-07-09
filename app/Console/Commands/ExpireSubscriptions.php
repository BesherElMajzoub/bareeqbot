<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Console\Command;

/**
 * Expires active subscriptions past their end date. Tenants left without an
 * active subscription are automatically over quota, which pauses their
 * automation at the runtime quota gate.
 */
class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark active subscriptions past their end date as expired.';

    public function handle(): int
    {
        $count = Subscription::withoutTenantScope()
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
