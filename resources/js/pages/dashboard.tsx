import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { t } = useTranslations();

    const stats = [
        { key: 'dashboard.events_received', value: '—' },
        { key: 'dashboard.replies_sent', value: '—' },
        { key: 'dashboard.dms_sent', value: '—' },
        { key: 'dashboard.success_rate', value: '—' },
    ];

    return (
        <>
            <Head title={t('dashboard.title')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">{t('dashboard.welcome')}</h1>
                    <p className="text-sm text-muted-foreground">{t('dashboard.subtitle')}</p>
                </div>

                <div className="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <div
                            key={stat.key}
                            className="rounded-xl border border-sidebar-border/70 p-5 dark:border-sidebar-border"
                        >
                            <p className="text-sm text-muted-foreground">{t(stat.key)}</p>
                            <p className="mt-2 text-3xl font-semibold tabular-nums">{stat.value}</p>
                        </div>
                    ))}
                </div>

                <div className="relative flex min-h-[50vh] flex-1 items-center justify-center rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <span className="text-sm text-muted-foreground">{t('common.coming_soon')}</span>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'لوحة التحكم',
            href: dashboard(),
        },
    ],
};
