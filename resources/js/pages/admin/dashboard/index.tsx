import { Head, Link, router } from '@inertiajs/react';
import React from 'react';
import {
    Users,
    CreditCard,
    TrendingUp,
    Bot,
    Layers,
    AlertCircle,
    Calendar,
    DollarSign,
    Activity,
    ArrowUpRight,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { AnalyticsPanel } from '@/components/analytics-panel';
import type {
    AnalyticsSeriesPoint,
    AnalyticsSummary,
    AnalyticsTopRule,
} from '@/components/analytics-panel';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes/admin';
import subscriptionRequests from '@/routes/admin/subscription-requests';

type Stats = {
    active_tenants: number;
    suspended_tenants: number;
    total_users: number;
    active_subscriptions: number;
    monthly_revenue: number;
    total_connections: number;
    webhook_received_today: number;
    webhook_failed_today: number;
};

type PendingRequest = {
    id: number;
    status: string;
    payer_note: string | null;
    created_at: string;
    tenant: { id: number; name: string } | null;
    plan_price: {
        duration_months: number;
        price: string;
        currency: string;
        plan: { name: string } | null;
    } | null;
};

type ExpiringSubscription = {
    id: number;
    ends_at: string;
    duration_months: number;
    tenant: { id: number; name: string } | null;
    plan: { name: string } | null;
};

type FailureLog = {
    id: number;
    platform: string;
    error: string | null;
    created_at: string;
    tenant: { name: string } | null;
    channel_connection: { name: string } | null;
};

type PlanDistribution = {
    plan_name: string;
    count: number;
};

type Props = {
    stats: Stats;
    recentPendingRequests: PendingRequest[];
    expiringSubscriptions: ExpiringSubscription[];
    recentFailures: FailureLog[];
    planDistribution: PlanDistribution[];
    analytics: {
        summary: AnalyticsSummary;
        series: AnalyticsSeriesPoint[];
        topRules: AnalyticsTopRule[];
    };
};

export default function AdminDashboard({
    stats,
    recentPendingRequests,
    expiringSubscriptions,
    recentFailures,
    planDistribution,
    analytics,
}: Props) {
    const { t, locale } = useTranslations();
    const isRtl = locale === 'ar';

    const approve = (id: number) => {
        router.post(subscriptionRequests.approve(id).url);
    };

    const reject = (id: number) => {
        const reason = window.prompt(t('admin.reject_reason'));
        if (reason) {
            router.post(subscriptionRequests.reject(id).url, { reason });
        }
    };

    const formatPrice = (value: number) => {
        return new Intl.NumberFormat(locale === 'ar' ? 'ar-SY' : 'en-US', {
            style: 'currency',
            currency: 'SYP',
            maximumFractionDigits: 0,
        }).format(value);
    };

    // Calculate plan total for distribution percentages
    const totalPlans = planDistribution.reduce((acc, curr) => acc + curr.count, 0) || 1;

    // Webhook success rate today
    const totalWebhooks = stats.webhook_received_today;
    const webhookSuccessRate = totalWebhooks > 0 
        ? Math.round(((totalWebhooks - stats.webhook_failed_today) / totalWebhooks) * 100) 
        : 100;

    return (
        <>
            <Head title={t('admin.dashboard')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                
                {/* Header */}
                <div className="flex flex-col gap-1">
                    <h1 className="text-2xl font-semibold tracking-tight font-sans">
                        {t('admin.dashboard')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {isRtl ? 'نظرة عامة على صحة وأداء المنصة والعملاء.' : 'Overview of platform health, customer performance, and activity.'}
                    </p>
                </div>

                {/* KPI Cards Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {/* Tenants Card */}
                    <Card className="hover:border-primary/20 transition-all duration-300">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {t('admin.stats_tenants')}
                            </CardTitle>
                            <Users className="size-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-extrabold tracking-tight tabular-nums">
                                {stats.active_tenants + stats.suspended_tenants}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1 flex items-center gap-1.5">
                                <span className="inline-flex size-2 rounded-full bg-emerald-500" />
                                <span className="font-semibold text-foreground">{stats.active_tenants}</span> {t('admin.active_tenants')}
                                <span className="inline-flex size-2 rounded-full bg-rose-500 ms-2" />
                                <span className="font-semibold text-foreground">{stats.suspended_tenants}</span> {t('admin.suspended_tenants')}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Active Subscriptions Card */}
                    <Card className="hover:border-primary/20 transition-all duration-300">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {t('admin.stats_active_sub')}
                            </CardTitle>
                            <CardContent className="p-0" />
                            <CreditCard className="size-4 text-violet-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-extrabold tracking-tight tabular-nums">
                                {stats.active_subscriptions}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {isRtl ? 'اشتراكات مدفوعة ونشطة حالياً' : 'Paid and currently active subscriptions'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Monthly Revenue Card */}
                    <Card className="hover:border-primary/20 transition-all duration-300">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {t('admin.stats_revenue')}
                            </CardTitle>
                            <DollarSign className="size-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-extrabold tracking-tight tabular-nums text-emerald-600 dark:text-emerald-500">
                                {formatPrice(stats.monthly_revenue)}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {isRtl ? 'إجمالي المبيعات للشهر الحالي' : 'Total sales logged during this current month'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Webhook Status Card */}
                    <Card className="hover:border-primary/20 transition-all duration-300">
                        <CardHeader className="flex flex-row items-center justify-between pb-2 space-y-0">
                            <CardTitle className="text-xs font-bold text-muted-foreground uppercase tracking-wider">
                                {t('admin.webhook_status')}
                            </CardTitle>
                            <Activity className={`size-4 ${stats.webhook_failed_today > 0 ? 'text-rose-500 animate-pulse' : 'text-emerald-500'}`} />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-extrabold tracking-tight tabular-nums">
                                {stats.webhook_received_today}
                            </div>
                            <div className="mt-1 flex items-center justify-between">
                                <p className="text-xs text-muted-foreground">
                                    {t('admin.webhook_health')}: <span className={`font-bold ${webhookSuccessRate < 95 ? 'text-rose-500' : 'text-emerald-600'}`}>{webhookSuccessRate}%</span>
                                </p>
                                {stats.webhook_failed_today > 0 && (
                                    <span className="text-[10px] bg-rose-500/10 text-rose-600 px-1.5 py-0.5 rounded font-bold">
                                        {stats.webhook_failed_today} {isRtl ? 'فشل' : 'failed'}
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Bot activity analytics chart & top rules */}
                <div className="space-y-4">
                    <div className="flex flex-col gap-1 border-t pt-4">
                        <h2 className="text-lg font-semibold tracking-tight">
                            {isRtl ? 'نشاط البوت التلقائي عبر المنصة' : 'Platform-Wide Bot Automation Activity'}
                        </h2>
                    </div>
                    <AnalyticsPanel
                        summary={analytics.summary}
                        series={analytics.series}
                        topRules={analytics.topRules}
                    />
                </div>

                {/* Grids for Actions & System Health */}
                <div className="grid gap-6 lg:grid-cols-3">
                    
                    {/* Column 1 & 2: Recent Pending Subscription Requests */}
                    <Card className="lg:col-span-2 hover:shadow-soft transition-all duration-300">
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle className="text-base font-bold">
                                    {t('admin.recent_pending_requests')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {isRtl ? 'طلبات بانتظار تأكيد الدفع أو التفعيل.' : 'Requests waiting for payment confirmation.'}
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="sm" asChild className="text-primary hover:text-primary">
                                <Link href={subscriptionRequests.index()}>
                                    {isRtl ? 'عرض الكل' : 'View All'}
                                    <ArrowUpRight className="size-4 ms-1" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="px-0">
                            {recentPendingRequests.length === 0 ? (
                                <p className="py-12 text-center text-xs text-muted-foreground italic">
                                    {t('admin.no_requests')}
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="text-muted-foreground bg-muted/20 text-xs border-y">
                                            <tr>
                                                <th className="p-3 text-start font-medium">{t('admin.tenant')}</th>
                                                <th className="p-3 text-start font-medium">{t('billing.plan')}</th>
                                                <th className="p-3 text-start font-medium">{t('billing.notes')}</th>
                                                <th className="p-3 text-end font-medium"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentPendingRequests.map((req) => (
                                                <tr key={req.id} className="border-b last:border-0 hover:bg-muted/10">
                                                    <td className="p-3 font-semibold">{req.tenant?.name ?? '—'}</td>
                                                    <td className="p-3">
                                                        <div className="flex flex-col">
                                                            <span className="font-medium">{req.plan_price?.plan?.name ?? '—'}</span>
                                                            <span className="text-[10px] text-muted-foreground tabular-nums">
                                                                {req.plan_price?.duration_months} {t('billing.months')}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="p-3 max-w-[200px] truncate text-xs text-muted-foreground">
                                                        {req.payer_note ?? '—'}
                                                    </td>
                                                    <td className="p-3 text-end">
                                                        <div className="flex justify-end gap-1.5">
                                                            <Button
                                                                size="sm"
                                                                className="h-7 px-2.5 text-xs bg-emerald-600 hover:bg-emerald-700 text-white"
                                                                onClick={() => approve(req.id)}
                                                            >
                                                                {t('admin.approve')}
                                                            </Button>
                                                            <Button
                                                                size="sm"
                                                                variant="destructive"
                                                                className="h-7 px-2.5 text-xs"
                                                                onClick={() => reject(req.id)}
                                                            >
                                                                {t('admin.reject')}
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Column 3: Plan Distribution */}
                    <Card className="hover:shadow-soft transition-all duration-300 flex flex-col justify-between">
                        <div>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base font-bold">
                                    {t('admin.plan_distribution')}
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {isRtl ? 'توزيع الاشتراكات الفعّالة حسب الباقات.' : 'Breakdown of active plan subscriptions.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {planDistribution.length === 0 ? (
                                    <p className="py-12 text-center text-xs text-muted-foreground italic">
                                        {t('dashboard.no_data')}
                                    </p>
                                ) : (
                                    planDistribution.map((plan) => {
                                        const percentage = Math.round((plan.count / totalPlans) * 100);
                                        return (
                                            <div key={plan.plan_name} className="space-y-1.5">
                                                <div className="flex items-center justify-between text-xs font-bold">
                                                    <span className="text-foreground">{plan.plan_name}</span>
                                                    <span className="text-primary tabular-nums">
                                                        {plan.count} ({percentage}%)
                                                    </span>
                                                </div>
                                                <div className="h-2 w-full overflow-hidden rounded-full bg-muted dark:bg-muted/30">
                                                    <div
                                                        className="h-full rounded-full bg-primary transition-all duration-500"
                                                        style={{
                                                            width: `${percentage}%`,
                                                        }}
                                                    />
                                                </div>
                                            </div>
                                        );
                                    })
                                )}
                            </CardContent>
                        </div>
                    </Card>

                    {/* Expiring Subscriptions in Next 7 Days */}
                    <Card className="hover:shadow-soft transition-all duration-300">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base font-bold">
                                {t('admin.expiring_subs')}
                            </CardTitle>
                            <CardDescription className="text-xs">
                                {isRtl ? 'الاشتراكات الفعّالة التي تنتهي خلال الـ 7 أيام القادمة.' : 'Subscribed accounts ending in the next 7 days.'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="px-0">
                            {expiringSubscriptions.length === 0 ? (
                                <p className="py-12 text-center text-xs text-muted-foreground italic">
                                    {isRtl ? 'لا توجد اشتراكات تنتهي قريباً.' : 'No subscriptions ending soon.'}
                                </p>
                            ) : (
                                <div className="space-y-0 border-t">
                                    {expiringSubscriptions.map((sub) => {
                                        const daysLeft = Math.max(
                                            0,
                                            Math.ceil(
                                                (new Date(sub.ends_at).getTime() - new Date().getTime()) /
                                                    (1000 * 60 * 60 * 24),
                                            ),
                                        );
                                        return (
                                            <div
                                                key={sub.id}
                                                className="flex items-center justify-between p-3.5 border-b last:border-0 hover:bg-muted/10"
                                            >
                                                <div className="flex flex-col gap-0.5">
                                                    <span className="text-sm font-semibold">{sub.tenant?.name ?? '—'}</span>
                                                    <span className="text-[10px] text-muted-foreground flex items-center gap-1.5">
                                                        <span>{sub.plan?.name}</span>
                                                        <span className="inline-block size-1 rounded-full bg-muted-foreground/40" />
                                                        <span className="tabular-nums">{new Date(sub.ends_at).toLocaleDateString()}</span>
                                                    </span>
                                                </div>
                                                <Badge
                                                    variant="secondary"
                                                    className="font-bold tabular-nums text-xs"
                                                >
                                                    {daysLeft} {t('admin.days_left')}
                                                </Badge>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* System Failures / Error Logs */}
                    <Card className="lg:col-span-2 hover:shadow-soft transition-all duration-300">
                        <CardHeader className="pb-3 flex flex-row items-center justify-between">
                            <div>
                                <CardTitle className="text-base font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                                    <AlertCircle className="size-4.5" />
                                    <span>{t('admin.recent_failures')}</span>
                                </CardTitle>
                                <CardDescription className="text-xs">
                                    {isRtl ? 'آخر إخفاقات البوت المسجلة في عمليات الرد الآلي.' : 'Last rule reply log execution failures.'}
                                </CardDescription>
                            </div>
                        </CardHeader>
                        <CardContent className="px-0">
                            {recentFailures.length === 0 ? (
                                <p className="py-12 text-center text-xs text-emerald-600 dark:text-emerald-500 font-bold italic">
                                    {isRtl ? 'لا توجد أخطاء نظام مسجلة مؤخراً. كل شيء يعمل بكفاءة! ✨' : 'No recent failures logged. Everything is healthy! ✨'}
                                </p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="text-muted-foreground bg-muted/20 text-xs border-y">
                                            <tr>
                                                <th className="p-3 text-start font-medium">{t('admin.tenant')}</th>
                                                <th className="p-3 text-start font-medium">{isRtl ? 'القناة' : 'Channel'}</th>
                                                <th className="p-3 text-start font-medium">{t('admin.failure_error')}</th>
                                                <th className="p-3 text-end font-medium">{isRtl ? 'التاريخ' : 'Date'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {recentFailures.map((log) => (
                                                <tr key={log.id} className="border-b last:border-0 hover:bg-muted/10">
                                                    <td className="p-3 font-semibold text-xs">{log.tenant?.name ?? '—'}</td>
                                                    <td className="p-3 text-xs">
                                                        <div className="flex flex-col">
                                                            <span className="font-semibold uppercase text-[10px] text-muted-foreground">
                                                                {log.platform}
                                                            </span>
                                                            <span className="font-medium truncate max-w-[120px]">
                                                                {log.channel_connection?.name ?? '—'}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td className="p-3 text-xs text-rose-600 dark:text-rose-400 font-medium max-w-[250px] truncate" title={log.error ?? ''}>
                                                        {log.error ?? 'Unknown automation reply error'}
                                                    </td>
                                                    <td className="p-3 text-end text-xs text-muted-foreground tabular-nums">
                                                        {new Date(log.created_at).toLocaleString()}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                </div>

            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'لوحة التحكم', href: dashboard().url }],
};
