import { Head, Link } from '@inertiajs/react';
import { History } from 'lucide-react';
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
