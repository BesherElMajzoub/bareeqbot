<?php

namespace App\Http\Controllers;

use App\Actions\Billing\SubmitSubscriptionRequest;
use App\Http\Requests\Billing\StoreSubscriptionRequestRequest;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(TenantContext $tenantContext): Response
    {
        $tenant = $tenantContext->current();

        return Inertia::render('billing/index', [
            'plans' => Plan::with('prices')
                ->where('is_active', true)
                ->orderBy('sort')
                ->get(),
            'activeSubscription' => $tenant?->activeSubscription()?->load('plan'),
            'requests' => $tenant
                ? $tenant->subscriptionRequests()->with('planPrice.plan')->latest()->take(10)->get()
                : [],
        ]);
    }

    public function store(
        StoreSubscriptionRequestRequest $request,
        SubmitSubscriptionRequest $submit,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $planPrice = PlanPrice::findOrFail($request->integer('plan_price_id'));

        $proofPath = $request->file('payment_proof')?->store(
            'payment-proofs',
            config('bariq.billing.proof_disk'),
        );

        $submit->handle($tenant, $planPrice, $request->input('payer_note'), $proofPath ?: null);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('billing.request_submitted')]);

        return to_route('billing.index');
    }
}
