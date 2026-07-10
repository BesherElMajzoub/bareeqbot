<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::with('prices')
            ->orderBy('sort')
            ->get();

        return Inertia::render('admin/plans/index', [
            'plans' => $plans,
        ]);
    }

    public function update(Plan $plan, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'max_pages' => ['required', 'integer', 'min:1'],
            'prices' => ['required', 'array'],
            'prices.*.duration_months' => ['required', 'integer', 'in:1,3,6,12'],
            'prices.*.platform_scope' => ['required', 'string', 'in:facebook,facebook_instagram'],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($plan, $data) {
            $plan->update([
                'name' => $data['name'],
                'max_pages' => $data['max_pages'],
                'features' => array_merge($plan->features ?? [], ['max_pages' => $data['max_pages']]),
            ]);

            foreach ($data['prices'] as $priceData) {
                $plan->prices()->updateOrCreate(
                    [
                        'duration_months' => $priceData['duration_months'],
                        'platform_scope' => $priceData['platform_scope'],
                    ],
                    [
                        'price' => $priceData['price'],
                        'currency' => config('bariq.billing.currency', 'SYP'),
                        'is_active' => true,
                    ]
                );
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('admin.plan_updated')]);

        return back();
    }
}
