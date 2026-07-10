<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionRequestController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\WebhookEventController;
use App\Http\Controllers\AnalyticsLogController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

// Public legal pages (required for Meta app review / going live).
Route::view('privacy', 'legal.privacy')->name('legal.privacy');
Route::view('terms', 'legal.terms')->name('legal.terms');
Route::view('data-deletion', 'legal.data-deletion')->name('legal.data-deletion');

// Public — webhook verification (GET challenge) + signed event ingestion (POST).
Route::middleware('throttle:meta-webhook')->group(function () {
    Route::get('webhooks/meta', [WebhookController::class, 'verify'])->name('webhooks.meta.verify');
    Route::post('webhooks/meta', [WebhookController::class, 'receive'])
        ->middleware('meta.signature')
        ->name('webhooks.meta.receive');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Tenant analytics — filtered reply log.
    Route::get('analytics/logs', [AnalyticsLogController::class, 'index'])->name('analytics.logs');

    // Tenant billing (manual subscriptions).
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/requests', [BillingController::class, 'store'])->name('billing.requests.store');
    Route::post('billing/subscription/cancel', [BillingController::class, 'cancel'])->name('billing.subscription.cancel');

    // Tenant channel connections (Facebook / Instagram via Meta OAuth).
    Route::get('connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::get('connections/facebook/redirect', [ConnectionController::class, 'redirect'])->name('connections.facebook.redirect');
    Route::get('connections/facebook/callback', [ConnectionController::class, 'callback'])
        ->name('connections.facebook.callback')
        ->middleware('throttle:10,1');
    Route::post('connections', [ConnectionController::class, 'store'])->name('connections.store');
    Route::delete('connections/{channelConnection}', [ConnectionController::class, 'destroy'])->name('connections.destroy');

    // Automation rules (comment auto-replies).
    Route::get('rules', [RuleController::class, 'index'])->name('rules.index');
    Route::post('rules', [RuleController::class, 'store'])->name('rules.store');
    Route::put('rules/{automationRule}', [RuleController::class, 'update'])->name('rules.update');
    Route::delete('rules/{automationRule}', [RuleController::class, 'destroy'])->name('rules.destroy');

    // Platform admin.
    Route::middleware('platform.staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('subscription-requests', [SubscriptionRequestController::class, 'index'])
            ->name('subscription-requests.index');
        Route::post('subscription-requests/{subscriptionRequest}/approve', [SubscriptionRequestController::class, 'approve'])
            ->name('subscription-requests.approve');
        Route::post('subscription-requests/{subscriptionRequest}/reject', [SubscriptionRequestController::class, 'reject'])
            ->name('subscription-requests.reject');

        Route::get('analytics', AnalyticsController::class)->name('analytics');

        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');

        Route::get('webhook-events', [WebhookEventController::class, 'index'])->name('webhook-events.index');
        Route::get('webhook-events/{webhookEvent}', [WebhookEventController::class, 'show'])->name('webhook-events.show');

        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    });
});

require __DIR__.'/settings.php';
