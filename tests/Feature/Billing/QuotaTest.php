<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\Billing\SubscriptionQuota;

test('quota reflects the active subscription plan', function () {
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create(['max_pages' => 5]);
    Subscription::factory()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);

    $quota = app(SubscriptionQuota::class);

    expect($quota->hasActiveSubscription($tenant))->toBeTrue()
        ->and($quota->maxPages($tenant))->toBe(5)
        ->and($quota->usedPages($tenant))->toBe(0)
        ->and($quota->remainingPages($tenant))->toBe(5)
        ->and($quota->canConnectMore($tenant))->toBeTrue();
});

test('an expired subscription grants no quota', function () {
    $tenant = Tenant::factory()->create();
    $plan = Plan::factory()->create(['max_pages' => 5]);
    Subscription::factory()->expired()->create(['tenant_id' => $tenant->id, 'plan_id' => $plan->id]);

    $quota = app(SubscriptionQuota::class);

    expect($quota->hasActiveSubscription($tenant))->toBeFalse()
        ->and($quota->maxPages($tenant))->toBe(0)
        ->and($quota->canConnectMore($tenant))->toBeFalse();
});

test('a tenant with no subscription has zero quota', function () {
    $tenant = Tenant::factory()->create();

    $quota = app(SubscriptionQuota::class);

    expect($quota->hasActiveSubscription($tenant))->toBeFalse()
        ->and($quota->maxPages($tenant))->toBe(0)
        ->and($quota->canConnectMore($tenant))->toBeFalse();
});
