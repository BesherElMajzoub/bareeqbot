<?php

use App\Actions\Billing\ApproveSubscriptionRequest;
use App\Actions\Billing\SubmitSubscriptionRequest;
use App\Enums\SubscriptionRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionApproved;
use App\Notifications\SubscriptionRequestSubmitted;
use Illuminate\Support\Facades\Notification;

test('submitting a request creates a pending row and notifies staff', function () {
    Notification::fake();
    $staff = User::factory()->create(['is_platform_staff' => true]);
    $tenant = Tenant::factory()->create();
    $price = PlanPrice::factory()->create();

    $request = app(SubmitSubscriptionRequest::class)->handle($tenant, $price);

    expect($request->status)->toBe(SubscriptionRequestStatus::Pending)
        ->and($request->tenant_id)->toBe($tenant->id);
    Notification::assertSentTo($staff, SubscriptionRequestSubmitted::class);
});

test('approving activates a subscription, marks the request approved, and notifies the owner', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $plan = Plan::factory()->create(['max_pages' => 5]);
    $price = PlanPrice::factory()->create(['plan_id' => $plan->id, 'duration_months' => 3]);
    $reviewer = User::factory()->create(['is_platform_staff' => true]);
    $request = SubscriptionRequest::factory()->create(['tenant_id' => $tenant->id, 'plan_price_id' => $price->id]);

    $subscription = app(ApproveSubscriptionRequest::class)->handle($request, $reviewer);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->tenant_id)->toBe($tenant->id)
        ->and($subscription->plan_id)->toBe($plan->id)
        ->and($subscription->ends_at->toDateString())->toBe(now()->addMonths(3)->toDateString())
        ->and($request->fresh()->status)->toBe(SubscriptionRequestStatus::Approved)
        ->and($request->fresh()->reviewed_by)->toBe($reviewer->id);
    Notification::assertSentTo($owner, SubscriptionApproved::class);
});

test('a tenant has at most one active subscription', function () {
    Notification::fake();
    $tenant = Tenant::factory()->create();
    $price = PlanPrice::factory()->create();
    $reviewer = User::factory()->create(['is_platform_staff' => true]);

    foreach (range(1, 2) as $ignored) {
        $request = SubscriptionRequest::factory()->create(['tenant_id' => $tenant->id, 'plan_price_id' => $price->id]);
        app(ApproveSubscriptionRequest::class)->handle($request, $reviewer);
    }

    $all = Subscription::withoutTenantScope()->where('tenant_id', $tenant->id);
    expect((clone $all)->count())->toBe(2)
        ->and((clone $all)->where('status', SubscriptionStatus::Active)->count())->toBe(1);
});

test('a non-pending request cannot be approved', function () {
    $reviewer = User::factory()->create(['is_platform_staff' => true]);
    $request = SubscriptionRequest::factory()->approved()->create();

    app(ApproveSubscriptionRequest::class)->handle($request, $reviewer);
})->throws(RuntimeException::class);
