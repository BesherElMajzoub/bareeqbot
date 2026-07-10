<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReplyLogStatus;
use App\Enums\SubscriptionRequestStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Enums\WebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\Subscription;
use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Analytics\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(AnalyticsService $analytics): Response
    {
        // 1. Tenants metrics (withoutTenantScope not needed on Tenant since Tenant itself defines the scope boundary)
        $activeTenantsCount = Tenant::where('status', TenantStatus::Active)->count();
        $suspendedTenantsCount = Tenant::where('status', TenantStatus::Suspended)->count();

        // 2. Users metric
        $totalUsersCount = User::count();

        // 3. Subscriptions metrics
        $activeSubscriptionsCount = Subscription::withoutTenantScope()->active()->count();
        $monthlyRevenue = Subscription::withoutTenantScope()
            ->where('starts_at', '>=', now()->startOfMonth())
            ->sum('price');

        // 4. Channel connections
        $totalConnectionsCount = ChannelConnection::withoutTenantScope()->count();

        // 5. Webhooks metrics today
        $webhookReceivedToday = WebhookEvent::where('received_at', '>=', now()->startOfDay())->count();
        $webhookFailedToday = WebhookEvent::where('received_at', '>=', now()->startOfDay())
            ->where('status', WebhookEventStatus::Failed)
            ->count();

        // 6. Recent pending subscription requests
        $recentPendingRequests = SubscriptionRequest::withoutTenantScope()
            ->with(['tenant', 'planPrice.plan'])
            ->where('status', SubscriptionRequestStatus::Pending)
            ->latest()
            ->take(5)
            ->get();

        // 7. Expiring subscriptions in next 7 days
        $expiringSubscriptions = Subscription::withoutTenantScope()
            ->with(['tenant', 'plan'])
            ->where('status', SubscriptionStatus::Active)
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->orderBy('ends_at')
            ->take(5)
            ->get();

        // 8. Recent reply failures
        $recentFailures = ReplyLog::withoutTenantScope()
            ->with(['tenant', 'channelConnection'])
            ->where('status', ReplyLogStatus::Failed)
            ->latest()
            ->take(5)
            ->get();

        // 9. Plan distribution
        $planDistribution = Subscription::withoutTenantScope()
            ->where('subscriptions.status', SubscriptionStatus::Active)
            ->where('subscriptions.ends_at', '>', now())
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->selectRaw('plans.name as plan_name, count(*) as count')
            ->groupBy('plans.id', 'plans.name')
            ->get();

        // 10. Platform wide live bot analytics
        $summary = $analytics->summary(null);
        $series = $analytics->dailySeries(null);
        $topRules = $analytics->topRules(null);

        return Inertia::render('admin/dashboard/index', [
            'stats' => [
                'active_tenants' => $activeTenantsCount,
                'suspended_tenants' => $suspendedTenantsCount,
                'total_users' => $totalUsersCount,
                'active_subscriptions' => $activeSubscriptionsCount,
                'monthly_revenue' => (float) $monthlyRevenue,
                'total_connections' => $totalConnectionsCount,
                'webhook_received_today' => $webhookReceivedToday,
                'webhook_failed_today' => $webhookFailedToday,
            ],
            'recentPendingRequests' => $recentPendingRequests,
            'expiringSubscriptions' => $expiringSubscriptions,
            'recentFailures' => $recentFailures,
            'planDistribution' => $planDistribution,
            'analytics' => [
                'summary' => $summary,
                'series' => $series,
                'topRules' => $topRules,
            ],
        ]);
    }
}
