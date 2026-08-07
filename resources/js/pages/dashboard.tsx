import { Head, Link, usePage } from '@inertiajs/react';
import { History, Calendar, CreditCard } from 'lucide-react';
import { AnalyticsPanel } from '@/components/analytics-panel';
import type {
    AnalyticsSeriesPoint,
    AnalyticsSummary,
    AnalyticsTopRule,
} from '@/components/analytics-panel';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes';
import analytics from '@/routes/analytics';

type Props = {
    summary: AnalyticsSummary;
    series: AnalyticsSeriesPoint[];
    topRules: AnalyticsTopRule[];
};

export default function Dashboard({ summary, series, topRules }: Props) {
    const { t } = useTranslations();
    const { auth } = usePage<{
        auth?: {
            subscription?: {
                plan_name: string;
                ends_at: string;
                days_left: number;
                is_active: boolean;
            };
        };
    }>().props;

    return (
        <>
            <Head title={t('dashboard.title')} />
            <div className="relative flex flex-col gap-8 overflow-hidden rounded-3xl border border-primary/10 bg-gradient-to-br from-card via-purple-50/30 to-primary/5 p-6 shadow-soft transition-all duration-300 md:p-8 dark:border-primary/20 dark:via-purple-950/5 dark:to-primary/10">
                {/* Dotted decorative background inside dashboard panel */}
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(var(--primary)_1px,transparent_1px)] bg-[size:22px_22px] opacity-[0.06]" />
                <div className="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary via-purple-500 to-accent" />

                {/* Dashboard Header Banner */}
                <div className="relative z-10 flex flex-col gap-4 border-b border-border/50 pb-6 sm:flex-row sm:items-center sm:justify-between">
                    <div className="space-y-1.5 text-start">
                        <h1 className="flex items-center gap-2 text-2xl font-black text-foreground sm:text-3xl">
                            <span>{t('dashboard.welcome')}</span>
                            <span className="animate-bounce text-xl">👋</span>
                        </h1>
                        <p className="text-sm font-semibold text-muted-foreground">
                            {t('dashboard.subtitle')}
                        </p>
                    </div>

                    <Link
                        href={analytics.logs().url}
                        className="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-primary/25 bg-primary/5 px-5 text-sm font-bold text-primary shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:bg-primary hover:text-primary-foreground hover:shadow-soft active:translate-y-0"
                    >
                        <History className="size-4" />
                        <span>{t('dashboard.view_logs')}</span>
                    </Link>
                </div>

                {/* Subscription Expiration Widget */}
                {auth?.subscription && (
                    <div className="relative z-10 flex flex-col gap-3 rounded-2xl border border-primary/20 bg-card/80 p-4 shadow-sm backdrop-blur-sm sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <CreditCard className="h-5 w-5" />
                            </div>
                            <div className="space-y-0.5">
                                <div className="flex items-center gap-2">
                                    <span className="text-xs font-semibold text-muted-foreground">الخطة الحالية:</span>
                                    <span className="font-bold text-sm text-foreground">{auth.subscription.plan_name}</span>
                                </div>
                                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Calendar className="h-3.5 w-3.5" />
                                    <span>تاريخ انتهاء الاشتراك:</span>
                                    <span className="font-bold text-foreground">{auth.subscription.ends_at}</span>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ${
                                auth.subscription.days_left > 5
                                    ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                    : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                            }`}>
                                ⏳ متبقي {auth.subscription.days_left} يوماً
                            </span>
                            <Link
                                href="/billing"
                                className="inline-flex h-8 items-center rounded-xl bg-primary px-3 text-xs font-bold text-primary-foreground shadow-sm transition-transform hover:scale-105"
                            >
                                إدارة / تجديد
                            </Link>
                        </div>
                    </div>
                )}

                <div className="relative z-10">
                    <AnalyticsPanel
                        summary={summary}
                        series={series}
                        topRules={topRules}
                    />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'لوحة التحكم', href: dashboard() }],
};
