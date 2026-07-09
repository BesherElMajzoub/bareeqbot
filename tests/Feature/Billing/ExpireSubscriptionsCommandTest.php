<?php

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

test('the expire command marks past-due active subscriptions as expired', function () {
    $pastDue = Subscription::factory()->endingInThePast()->create();
    $current = Subscription::factory()->create();

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($pastDue->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($current->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('the expire command works across tenants without a bound tenant context', function () {
    Subscription::factory()->endingInThePast()->count(3)->create();

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect(Subscription::withoutTenantScope()->where('status', SubscriptionStatus::Expired)->count())->toBe(3);
});
