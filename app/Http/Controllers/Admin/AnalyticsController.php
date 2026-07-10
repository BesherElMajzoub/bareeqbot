<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(AnalyticsService $analytics): Response
    {
        // null tenant → platform-wide figures across all tenants.
        return Inertia::render('admin/analytics/index', [
            'summary' => $analytics->summary(null),
            'series' => $analytics->dailySeries(null),
            'topRules' => $analytics->topRules(null),
        ]);
    }
}
