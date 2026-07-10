import { Head } from '@inertiajs/react';
import { AnalyticsPanel } from '@/components/analytics-panel';
import type {
    AnalyticsSeriesPoint,
    AnalyticsSummary,
    AnalyticsTopRule,
} from '@/components/analytics-panel';
import { useTranslations } from '@/hooks/use-translations';
import { analytics } from '@/routes/admin';

type Props = {
    summary: AnalyticsSummary;
    series: AnalyticsSeriesPoint[];
    topRules: AnalyticsTopRule[];
};

export default function AdminAnalytics({ summary, series, topRules }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('admin.analytics')} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {t('admin.analytics')}
                </h1>
                <AnalyticsPanel
                    summary={summary}
                    series={series}
                    topRules={topRules}
                />
            </div>
        </>
    );
}

AdminAnalytics.layout = {
    breadcrumbs: [{ title: 'تحليلات المنصّة', href: analytics().url }],
};
