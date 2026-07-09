<?php

use App\Http\Controllers\Admin\SubscriptionRequestController;
use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Tenant billing (manual subscriptions).
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing/requests', [BillingController::class, 'store'])->name('billing.requests.store');

    // Platform admin.
    Route::middleware('platform.staff')->prefix('admin')->name('admin.')->group(function () {
        Route::get('subscription-requests', [SubscriptionRequestController::class, 'index'])
            ->name('subscription-requests.index');
        Route::post('subscription-requests/{subscriptionRequest}/approve', [SubscriptionRequestController::class, 'approve'])
            ->name('subscription-requests.approve');
        Route::post('subscription-requests/{subscriptionRequest}/reject', [SubscriptionRequestController::class, 'reject'])
            ->name('subscription-requests.reject');
    });
});

require __DIR__.'/settings.php';
