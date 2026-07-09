<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Billing\ApproveSubscriptionRequest;
use App\Actions\Billing\RejectSubscriptionRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectSubscriptionRequestRequest;
use App\Models\SubscriptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/subscription-requests/index', [
            'requests' => SubscriptionRequest::withoutTenantScope()
                ->with(['tenant', 'planPrice.plan', 'reviewer'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function approve(SubscriptionRequest $subscriptionRequest, ApproveSubscriptionRequest $approve, Request $request): RedirectResponse
    {
        $approve->handle($subscriptionRequest, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.approved')]);

        return back();
    }

    public function reject(RejectSubscriptionRequestRequest $request, SubscriptionRequest $subscriptionRequest, RejectSubscriptionRequest $reject): RedirectResponse
    {
        $reject->handle($subscriptionRequest, $request->user(), $request->string('reason')->toString());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.rejected')]);

        return back();
    }
}
