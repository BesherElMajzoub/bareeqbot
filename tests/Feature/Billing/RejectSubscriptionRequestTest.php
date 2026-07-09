<?php

use App\Actions\Billing\RejectSubscriptionRequest;
use App\Enums\SubscriptionRequestStatus;
use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\SubscriptionRejected;
use Illuminate\Support\Facades\Notification;

test('rejecting sets the status, reason, reviewer and notifies the owner', function () {
    Notification::fake();
    $owner = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $owner->id]);
    $reviewer = User::factory()->create(['is_platform_staff' => true]);
    $request = SubscriptionRequest::factory()->create(['tenant_id' => $tenant->id]);

    app(RejectSubscriptionRequest::class)->handle($request, $reviewer, 'Invalid receipt');

    $request->refresh();
    expect($request->status)->toBe(SubscriptionRequestStatus::Rejected)
        ->and($request->reject_reason)->toBe('Invalid receipt')
        ->and($request->reviewed_by)->toBe($reviewer->id);
    Notification::assertSentTo($owner, SubscriptionRejected::class);
});

test('a non-pending request cannot be rejected', function () {
    $reviewer = User::factory()->create(['is_platform_staff' => true]);
    $request = SubscriptionRequest::factory()->approved()->create();

    app(RejectSubscriptionRequest::class)->handle($request, $reviewer, 'too late');
})->throws(RuntimeException::class);
