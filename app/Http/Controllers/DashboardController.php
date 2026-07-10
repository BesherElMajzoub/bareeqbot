<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(TenantContext $tenantContext, AnalyticsService $analytics): Response|RedirectResponse
    {
        $user = auth()->user();
        if ($user !== null && $user->is_platform_staff) {
            return redirect()->route('admin.dashboard');
        }

        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        return Inertia::render('dashboard', [
            'summary' => $analytics->summary($tenant->id),
            'series' => $analytics->dailySeries($tenant->id),
            'topRules' => $analytics->topRules($tenant->id),
        ]);
    }
}
